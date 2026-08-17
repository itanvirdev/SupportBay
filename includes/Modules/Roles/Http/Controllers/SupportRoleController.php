<?php

declare(strict_types=1);

namespace SupportBay\Modules\Roles\Http\Controllers;

use InvalidArgumentException;
use SupportBay\Core\Http\RestResponse;
use SupportBay\Core\Authorization\CapabilityManager;
use SupportBay\Modules\Roles\Services\SupportRoleService;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class SupportRoleController {
  public function __construct(private readonly SupportRoleService $roles) {}

  public function registerRoutes(): void {
    register_rest_route('sbay/v1', '/roles', [
      ['methods' => 'GET', 'callback' => [$this, 'index'], 'permission_callback' => [$this, 'canManage']],
      ['methods' => 'POST', 'callback' => [$this, 'create'], 'permission_callback' => [$this, 'canManage']],
    ]);
    register_rest_route('sbay/v1', '/roles/(?P<slug>[a-z0-9_-]+)', [
      ['methods' => 'PUT', 'callback' => [$this, 'update'], 'permission_callback' => [$this, 'canManage']],
      ['methods' => 'DELETE', 'callback' => [$this, 'delete'], 'permission_callback' => [$this, 'canManage']],
    ]);
  }

  public function canManage(): bool|WP_Error {
    if (! is_user_logged_in()) { return new WP_Error('sbay_authentication_required', 'Authentication is required.', ['status' => 401]); }
    return current_user_can(CapabilityManager::MANAGE_ROLES)
      ? true
      : new WP_Error('sbay_permission_denied', 'You are not allowed to manage roles.', ['status' => 403]);
  }

  public function index(): WP_REST_Response {
    $items = array_map(static fn($role): array => $role->toArray(), $this->roles->all());
    return RestResponse::success($items, 'Roles retrieved.', [
      'total' => count($items),
      'capability_groups' => $this->roles->catalog(),
      'required_capabilities' => $this->roles->requiredCapabilities(),
    ]);
  }

  public function create(WP_REST_Request $request): WP_REST_Response {
    return $this->mutate(fn() => $this->roles->create((array) $request->get_json_params()), 201);
  }

  public function update(WP_REST_Request $request): WP_REST_Response {
    return $this->mutate(fn() => $this->roles->update(sanitize_key((string) $request['slug']), (array) $request->get_json_params()));
  }

  public function delete(WP_REST_Request $request): WP_REST_Response {
    try { $this->roles->delete(sanitize_key((string) $request['slug'])); }
    catch (InvalidArgumentException $exception) { return RestResponse::error($exception->getMessage(), 'ROLE_DELETE_FAILED', [], 409); }
    return RestResponse::success([], 'Role deleted.');
  }

  private function mutate(callable $callback, int $status = 200): WP_REST_Response {
    try { $role = $callback(); }
    catch (InvalidArgumentException $exception) { return RestResponse::error($exception->getMessage(), 'INVALID_ROLE', [], 422); }
    return RestResponse::success($role->toArray(), 'Role saved.', [], $status);
  }
}
