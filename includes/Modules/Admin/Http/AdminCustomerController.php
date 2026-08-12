<?php

declare(strict_types=1);

namespace SupportBay\Modules\Admin\Http;

use RuntimeException;
use SupportBay\Core\Authorization\CapabilityManager;
use SupportBay\Core\Http\RestResponse;
use SupportBay\Modules\Admin\Services\CustomerProfileService;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class AdminCustomerController {
  public function __construct(private readonly CustomerProfileService $profiles) {}

  public function registerRoutes(): void {
    register_rest_route('sbay/v1', '/admin/customers/(?P<id>\d+)/profile', [
      'methods' => 'GET',
      'callback' => [$this, 'show'],
      'permission_callback' => [$this, 'permissions'],
      'args' => ['id' => ['sanitize_callback' => 'absint']],
    ]);
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
