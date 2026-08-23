<?php

declare(strict_types=1);

namespace SupportBay\Modules\AssignRules\Http\Controllers;

use InvalidArgumentException;
use SupportBay\Core\Authorization\CapabilityManager;
use SupportBay\Core\Http\RestResponse;
use SupportBay\Modules\AssignRules\Entities\AssignRule;
use SupportBay\Modules\AssignRules\Services\AssignRuleService;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class AssignRuleController {
  public function __construct(private readonly AssignRuleService $rules) {}

  public function registerRoutes(): void {
    register_rest_route('sbay/v1', '/admin/assign-rules', [
      ['methods' => 'GET', 'callback' => [$this, 'index'], 'permission_callback' => [$this, 'permissions']],
      ['methods' => 'POST', 'callback' => [$this, 'create'], 'permission_callback' => [$this, 'permissions']],
    ]);
    register_rest_route('sbay/v1', '/admin/assign-rules/bulk', ['methods' => 'POST', 'callback' => [$this, 'bulk'], 'permission_callback' => [$this, 'permissions']]);
    foreach (['GET' => 'show', 'PUT' => 'update', 'DELETE' => 'delete'] as $method => $callback) {
      register_rest_route('sbay/v1', '/admin/assign-rules/(?P<id>\d+)', [
        'methods' => $method,
        'callback' => [$this, $callback],
        'permission_callback' => [$this, 'permissions'],
        'args' => ['id' => ['sanitize_callback' => 'absint']],
      ]);
    }
  }

  public function permissions(): bool|WP_Error {
    if (! is_user_logged_in()) { return new WP_Error('sbay_authentication_required', 'Authentication is required.', ['status' => 401]); }
    return current_user_can(CapabilityManager::MANAGE_SETTINGS) ? true : new WP_Error('sbay_permission_denied', 'You are not allowed to manage assign rules.', ['status' => 403]);
  }

  public function index(): WP_REST_Response {
    $items = $this->rules->all();
    return RestResponse::success(array_map(static fn(AssignRule $rule): array => $rule->toArray(), $items), 'Assign rules retrieved.', $this->rules->options() + ['total' => count($items)]);
  }

  public function show(WP_REST_Request $request): WP_REST_Response {
    $rule = $this->rules->find(absint($request['id']));
    return $rule ? RestResponse::success($rule->toArray(), 'Assign rule retrieved.') : RestResponse::error('Assign rule was not found.', 'ASSIGN_RULE_NOT_FOUND', [], 404);
  }

  public function create(WP_REST_Request $request): WP_REST_Response { return $this->mutate(fn(): AssignRule => $this->rules->create((array) $request->get_json_params()), 201); }
  public function update(WP_REST_Request $request): WP_REST_Response { return $this->mutate(fn(): ?AssignRule => $this->rules->update(absint($request['id']), (array) $request->get_json_params())); }

  public function delete(WP_REST_Request $request): WP_REST_Response {
    return $this->rules->delete(absint($request['id'])) ? RestResponse::success([], 'Assign rule deleted.') : RestResponse::error('Assign rule was not found.', 'ASSIGN_RULE_NOT_FOUND', [], 404);
  }

  public function bulk(WP_REST_Request $request): WP_REST_Response {
    try {
      $data = (array) $request->get_json_params();
      return RestResponse::success($this->rules->bulk((array) ($data['ids'] ?? []), sanitize_key((string) ($data['action'] ?? ''))), 'Bulk action completed.');
    } catch (InvalidArgumentException $exception) {
      return RestResponse::error($exception->getMessage(), 'INVALID_ASSIGN_RULE_BULK_ACTION', [], 422);
    }
  }

  private function mutate(callable $callback, int $status = 200): WP_REST_Response {
    try {
      $rule = $callback();
      return $rule ? RestResponse::success($rule->toArray(), 'Assign rule saved.', [], $status) : RestResponse::error('Assign rule was not found.', 'ASSIGN_RULE_NOT_FOUND', [], 404);
    } catch (InvalidArgumentException $exception) {
      return RestResponse::error($exception->getMessage(), 'INVALID_ASSIGN_RULE', [], 422);
    }
  }
}
