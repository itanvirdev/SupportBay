<?php

declare(strict_types=1);

namespace SupportBay\Modules\Departments\Http\Controllers;

use SupportBay\Core\Http\RestResponse;
use SupportBay\Modules\Departments\Entities\Department;
use SupportBay\Modules\Departments\Enums\DepartmentStatus;
use SupportBay\Modules\Departments\Services\DepartmentService;
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
    register_rest_route('sbay/v1', '/departments/(?P<id>\d+)', [
      'methods' => 'GET', 'callback' => [$this, 'show'],
      'permission_callback' => [$this, 'permissions'],
    ]);
  }

  public function permissions(): bool|WP_Error {
    if (! is_user_logged_in()) {
      return new WP_Error('sbay_authentication_required', 'Authentication is required.', ['status' => 401]);
    }

    return current_user_can('manage_options')
      ? true
      : new WP_Error('sbay_permission_denied', 'You are not allowed to manage departments.', ['status' => 403]);
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
