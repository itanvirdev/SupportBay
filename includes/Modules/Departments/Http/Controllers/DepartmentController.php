<?php

declare(strict_types=1);

namespace SupportBay\Modules\Departments\Http\Controllers;

use SupportBay\Core\Http\RestResponse;
use SupportBay\Core\Authorization\CapabilityManager;
use SupportBay\Modules\Departments\Entities\Department;
use SupportBay\Modules\Departments\Enums\DepartmentStatus;
use SupportBay\Modules\Departments\Services\DepartmentService;
use SupportBay\Modules\Tickets\Enums\TicketPriority;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class DepartmentController {
  public function __construct(private readonly DepartmentService $departments) {
  }

  public function registerRoutes(): void {
    register_rest_route('sbay/v1', '/departments', [
      'methods' => 'GET', 'callback' => [$this, 'index'],
      'permission_callback' => [$this, 'permissions'],
    ]);
    register_rest_route('sbay/v1', '/departments', [
      'methods' => 'POST', 'callback' => [$this, 'create'],
      'permission_callback' => [$this, 'canCreate'],
    ]);
    register_rest_route('sbay/v1', '/departments/(?P<id>\d+)', [
      'methods' => 'GET', 'callback' => [$this, 'show'],
      'permission_callback' => [$this, 'permissions'],
    ]);
    register_rest_route('sbay/v1', '/departments/(?P<id>\d+)', [
      'methods' => 'PUT', 'callback' => [$this, 'update'],
      'permission_callback' => [$this, 'canEdit'],
    ]);
  }

  public function permissions(): bool|WP_Error {
    if (! is_user_logged_in()) {
      return new WP_Error('sbay_authentication_required', 'Authentication is required.', ['status' => 401]);
    }

    return current_user_can(CapabilityManager::MANAGE_DEPARTMENTS)
      ? true
      : new WP_Error('sbay_permission_denied', 'You are not allowed to manage departments.', ['status' => 403]);
  }

  public function canCreate(): bool|WP_Error {
    return $this->requires(CapabilityManager::CREATE_DEPARTMENT);
  }

  public function canEdit(): bool|WP_Error {
    return $this->requires(CapabilityManager::EDIT_DEPARTMENT);
  }

  public function create(WP_REST_Request $request): WP_REST_Response {
    $data = $this->mutationData($request);

    if (! isset($data['name']) || $data['name'] === '') {
      return RestResponse::error('Department name is required.', 'DEPARTMENT_NAME_REQUIRED', [], 422);
    }

    $id = $this->departments->create($data);

    return RestResponse::success(
      $this->departments->find($id)->toArray(),
      'Department created.', [], 201,
    );
  }

  public function update(WP_REST_Request $request): WP_REST_Response {
    $department = $this->departments->update(
      absint($request->get_param('id')),
      $this->mutationData($request),
    );

    return $department
      ? RestResponse::success($department->toArray(), 'Department updated.')
      : RestResponse::error('Department was not found.', 'DEPARTMENT_NOT_FOUND', [], 404);
  }

  private function requires(string $capability): bool|WP_Error {
    if (! is_user_logged_in()) {
      return new WP_Error('sbay_authentication_required', 'Authentication is required.', ['status' => 401]);
    }

    return current_user_can($capability)
      ? true
      : new WP_Error('sbay_permission_denied', 'You are not allowed to modify departments.', ['status' => 403]);
  }

  /** @return array<string, mixed> */
  private function mutationData(WP_REST_Request $request): array {
    $data = [];

    if ($request->has_param('name')) {
      $data['name'] = sanitize_text_field((string) $request->get_param('name'));
    }
    if ($request->has_param('slug')) {
      $data['slug'] = sanitize_title((string) $request->get_param('slug'));
    }
    if ($request->has_param('status')) {
      $status = DepartmentStatus::tryFrom(sanitize_key((string) $request->get_param('status')));
      if ($status) {
        $data['status'] = $status->value;
      }
    }
    if ($request->has_param('default_priority')) {
      $priority = TicketPriority::tryFrom(sanitize_key((string) $request->get_param('default_priority')));
      if ($priority) {
        $data['default_priority'] = $priority->value;
      }
    }
    if ($request->has_param('sort_order')) {
      $data['sort_order'] = absint($request->get_param('sort_order'));
    }

    return $data;
  }

  public function index(WP_REST_Request $request): WP_REST_Response {
    $status = DepartmentStatus::tryFrom(sanitize_key((string) $request->get_param('status')));
    $items = $status
      ? array_values(array_filter(
        $this->departments->all(),
        static fn(Department $department): bool => $department->status() === $status,
      ))
      : $this->departments->all();

    return RestResponse::success(
      array_map(static fn(Department $department): array => $department->toArray(), $items),
      'Departments retrieved.',
      ['total' => count($items)],
    );
  }

  public function show(WP_REST_Request $request): WP_REST_Response {
    $department = $this->departments->find(absint($request->get_param('id')));

    return $department
      ? RestResponse::success($department->toArray(), 'Department retrieved.')
      : RestResponse::error('Department was not found.', 'DEPARTMENT_NOT_FOUND', [], 404);
  }
}
