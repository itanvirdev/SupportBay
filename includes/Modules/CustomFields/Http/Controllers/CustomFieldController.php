<?php

declare(strict_types=1);

namespace SupportBay\Modules\CustomFields\Http\Controllers;

use InvalidArgumentException;
use SupportBay\Core\Authorization\CapabilityManager;
use SupportBay\Core\Http\RestResponse;
use SupportBay\Modules\CustomFields\Entities\CustomField;
use SupportBay\Modules\CustomFields\Services\CustomFieldService;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class CustomFieldController {
  public function __construct(private readonly CustomFieldService $fields) {}

  public function registerRoutes(): void {
    register_rest_route('sbay/v1', '/custom-fields', [
      'methods' => 'GET', 'callback' => [$this, 'index'],
      'permission_callback' => [$this, 'canView'],
    ]);
    register_rest_route('sbay/v1', '/custom-fields', [
      'methods' => 'POST', 'callback' => [$this, 'create'],
      'permission_callback' => [$this, 'canManage'],
    ]);
    foreach (['GET' => 'show', 'PUT' => 'update', 'DELETE' => 'delete'] as $method => $callback) {
      register_rest_route('sbay/v1', '/custom-fields/(?P<id>\d+)', [
        'methods' => $method,
        'callback' => [$this, $callback],
        'permission_callback' => [$this, $method === 'GET' ? 'canView' : 'canManage'],
        'args' => ['id' => [
          'sanitize_callback' => 'absint',
          'validate_callback' => static fn(mixed $value): bool => is_numeric($value) && (int) $value > 0,
        ]],
      ]);
    }
  }

  public function canView(): bool|WP_Error { return $this->requires(CapabilityManager::VIEW_TICKETS); }
  public function canManage(): bool|WP_Error { return $this->requires(CapabilityManager::MANAGE_CUSTOM_FIELDS); }

  public function index(WP_REST_Request $request): WP_REST_Response {
    $items = $request->get_param('status') === 'active' ? $this->fields->active() : $this->fields->all();
    return RestResponse::success(
      array_map(static fn(CustomField $field): array => $field->toArray(), $items),
      'Custom fields retrieved.',
      ['total' => count($items)],
    );
  }

  public function show(WP_REST_Request $request): WP_REST_Response {
    $field = $this->fields->find(absint($request['id']));
    return $field
      ? RestResponse::success($field->toArray(), 'Custom field retrieved.')
      : RestResponse::error('Custom field was not found.', 'CUSTOM_FIELD_NOT_FOUND', [], 404);
  }

  public function create(WP_REST_Request $request): WP_REST_Response {
    return $this->mutate(fn(): CustomField => $this->fields->create((array) $request->get_json_params()), 201);
  }

  public function update(WP_REST_Request $request): WP_REST_Response {
    return $this->mutate(fn(): ?CustomField => $this->fields->update(
      absint($request['id']),
      (array) $request->get_json_params(),
    ));
  }

  public function delete(WP_REST_Request $request): WP_REST_Response {
    try { $deleted = $this->fields->delete(absint($request['id'])); }
    catch (InvalidArgumentException $exception) {
      return RestResponse::error($exception->getMessage(), 'CUSTOM_FIELD_IN_USE', [], 409);
    }
    return $deleted
      ? RestResponse::success([], 'Custom field deleted.')
      : RestResponse::error('Custom field was not found.', 'CUSTOM_FIELD_NOT_FOUND', [], 404);
  }

  private function mutate(callable $callback, int $status = 200): WP_REST_Response {
    try {
      $field = $callback();
      return $field
        ? RestResponse::success($field->toArray(), 'Custom field saved.', [], $status)
        : RestResponse::error('Custom field was not found.', 'CUSTOM_FIELD_NOT_FOUND', [], 404);
    } catch (InvalidArgumentException $exception) {
      return RestResponse::error($exception->getMessage(), 'INVALID_CUSTOM_FIELD', [], 422);
    }
  }

  private function requires(string $capability): bool|WP_Error {
    if (! is_user_logged_in()) {
      return new WP_Error('sbay_authentication_required', 'Authentication is required.', ['status' => 401]);
    }
    return current_user_can($capability)
      ? true
      : new WP_Error('sbay_permission_denied', 'You are not allowed to access custom fields.', ['status' => 403]);
  }
}
