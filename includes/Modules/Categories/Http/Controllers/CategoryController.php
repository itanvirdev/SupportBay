<?php

declare(strict_types=1);

namespace SupportBay\Modules\Categories\Http\Controllers;

use InvalidArgumentException;
use SupportBay\Core\Authorization\CapabilityManager;
use SupportBay\Core\Http\RestResponse;
use SupportBay\Modules\Categories\Entities\Category;
use SupportBay\Modules\Categories\Services\CategoryService;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class CategoryController {
  public function __construct(
    private readonly CategoryService $categories,
  ) {
  }

  public function registerRoutes(): void {
    register_rest_route('sbay/v1', '/categories', [
      'methods' => 'GET',
      'callback' => [$this, 'index'],
      'permission_callback' => [$this, 'canView'],
    ]);
    register_rest_route('sbay/v1', '/categories', [
      'methods' => 'POST',
      'callback' => [$this, 'create'],
      'permission_callback' => [$this, 'canManage'],
    ]);

    foreach (['GET' => 'show', 'PUT' => 'update', 'DELETE' => 'delete'] as $method => $callback) {
      register_rest_route('sbay/v1', '/categories/(?P<id>\d+)', [
        'methods' => $method,
        'callback' => [$this, $callback],
        'permission_callback' => [
          $this,
          $method === 'GET' ? 'canView' : 'canManage',
        ],
        'args' => [
          'id' => [
            'sanitize_callback' => 'absint',
            'validate_callback' => static fn(mixed $value): bool =>
              is_numeric($value) && (int) $value > 0,
          ],
        ],
      ]);
    }
  }

  public function canView(): bool|WP_Error {
    return $this->requires(CapabilityManager::VIEW_TICKETS);
  }

  public function canManage(): bool|WP_Error {
    return $this->requires(CapabilityManager::MANAGE_CATEGORIES);
  }

  public function index(WP_REST_Request $request): WP_REST_Response {
    $items = $request->get_param('status') === 'active'
      ? $this->categories->active()
      : $this->categories->all();

    return RestResponse::success(
      array_map(
        static fn(Category $item): array => $item->toArray(),
        $items,
      ),
      'Categories retrieved.',
      ['total' => count($items)],
    );
  }

  public function show(WP_REST_Request $request): WP_REST_Response {
    $item = $this->categories->find(absint($request['id']));

    return $item
      ? RestResponse::success($item->toArray(), 'Category retrieved.')
      : RestResponse::error(
        'Category was not found.',
        'CATEGORY_NOT_FOUND',
        [],
        404,
      );
  }

  public function create(WP_REST_Request $request): WP_REST_Response {
    return $this->mutate(
      fn(): Category => $this->categories->create(
        (array) $request->get_json_params()
      ),
      201,
    );
  }

  public function update(WP_REST_Request $request): WP_REST_Response {
    return $this->mutate(
      fn(): ?Category => $this->categories->update(
        absint($request['id']),
        (array) $request->get_json_params(),
      ),
    );
  }

  public function delete(WP_REST_Request $request): WP_REST_Response {
    try {
      $deleted = $this->categories->delete(absint($request['id']));
    } catch (InvalidArgumentException $exception) {
      return RestResponse::error(
        $exception->getMessage(),
        'CATEGORY_IN_USE',
        [],
        409,
      );
    }

    return $deleted
      ? RestResponse::success([], 'Category deleted.')
      : RestResponse::error(
        'Category was not found.',
        'CATEGORY_NOT_FOUND',
        [],
        404,
      );
  }

  private function mutate(
    callable $callback,
    int $status = 200,
  ): WP_REST_Response {
    try {
      $item = $callback();

      return $item
        ? RestResponse::success(
          $item->toArray(),
          'Category saved.',
          [],
          $status,
        )
        : RestResponse::error(
          'Category was not found.',
          'CATEGORY_NOT_FOUND',
          [],
          404,
        );
    } catch (InvalidArgumentException $exception) {
      return RestResponse::error(
        $exception->getMessage(),
        'INVALID_CATEGORY',
        [],
        422,
      );
    }
  }

  private function requires(string $capability): bool|WP_Error {
    if (! is_user_logged_in()) {
      return new WP_Error(
        'sbay_authentication_required',
        'Authentication is required.',
        ['status' => 401],
      );
    }

    return current_user_can($capability)
      ? true
      : new WP_Error(
        'sbay_permission_denied',
        'You are not allowed to access categories.',
        ['status' => 403],
      );
  }
}
