<?php

declare(strict_types=1);

namespace SupportBay\Modules\Admin\Http;

use RuntimeException;
use SupportBay\Core\Authorization\CapabilityManager;
use SupportBay\Core\Http\RestResponse;
use SupportBay\Modules\Admin\Services\CustomerProfileService;
use SupportBay\Modules\Admin\Services\CustomerDirectoryService;
use SupportBay\Modules\Admin\Data\CustomerDirectoryQuery;
use SupportBay\Modules\Customers\Enums\CustomerSource;
use SupportBay\Modules\Customers\Enums\CustomerState;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class AdminCustomerController {
  public function __construct(
    private readonly CustomerProfileService $profiles,
    private readonly CustomerDirectoryService $directory,
  ) {}

  public function registerRoutes(): void {
    register_rest_route('sbay/v1', '/admin/customers/directory', [
      'methods' => 'GET',
      'callback' => [$this, 'index'],
      'permission_callback' => [$this, 'permissions'],
    ]);
    register_rest_route('sbay/v1', '/admin/customers/(?P<id>\d+)/profile', [
      'methods' => 'GET',
      'callback' => [$this, 'show'],
      'permission_callback' => [$this, 'permissions'],
      'args' => ['id' => ['sanitize_callback' => 'absint']],
    ]);
  }

  public function index(WP_REST_Request $request): WP_REST_Response {
    $page = max(1, absint($request->get_param('page')) ?: 1);
    $perPage = min(100, max(1, absint($request->get_param('per_page')) ?: 20));
    $state = CustomerState::tryFrom(sanitize_key((string) $request->get_param('state')));
    $source = CustomerSource::tryFrom(sanitize_key((string) $request->get_param('source')));
    $result = $this->directory->search(new CustomerDirectoryQuery(
      page: $page,
      perPage: $perPage,
      search: sanitize_text_field(wp_unslash((string) $request->get_param('search'))) ?: null,
      state: $state?->value,
      source: $source?->value,
      orderBy: sanitize_key((string) $request->get_param('orderby')) ?: 'last_activity',
      direction: sanitize_key((string) $request->get_param('order')) ?: 'desc',
    ));

    return RestResponse::success(
      array_map(static fn($item): array => $item->toArray(), $result['items']),
      'Customer directory retrieved.',
      [
        'page' => $page,
        'per_page' => $perPage,
        'total' => $result['total'],
        'total_pages' => (int) ceil($result['total'] / $perPage),
      ],
    );
  }

  public function permissions(): bool|WP_Error {
    if (! is_user_logged_in()) {
      return new WP_Error('sbay_authentication_required', 'Authentication is required.', ['status' => 401]);
    }

    return current_user_can(CapabilityManager::MANAGE_CUSTOMERS)
      ? true
      : new WP_Error('sbay_permission_denied', 'You are not allowed to manage customers.', ['status' => 403]);
  }

  public function show(WP_REST_Request $request): WP_REST_Response {
    try {
      return RestResponse::success(
        $this->profiles->profile((int) $request->get_param('id')),
        'Customer profile retrieved.',
      );
    } catch (RuntimeException $exception) {
      return RestResponse::error($exception->getMessage(), 'CUSTOMER_NOT_FOUND', [], 404);
    }
  }
}
