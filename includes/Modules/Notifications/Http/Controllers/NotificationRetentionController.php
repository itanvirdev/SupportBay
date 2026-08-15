<?php

declare(strict_types=1);

namespace SupportBay\Modules\Notifications\Http\Controllers;

use InvalidArgumentException;
use SupportBay\Core\Authorization\CapabilityManager;
use SupportBay\Core\Http\RestResponse;
use SupportBay\Modules\Notifications\Services\NotificationRetentionService;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class NotificationRetentionController {
  private const ROUTE = '/admin/notification-retention';

  public function __construct(
    private readonly NotificationRetentionService $retention,
  ) {
  }

  public function registerRoutes(): void {
    register_rest_route('sbay/v1', self::ROUTE, [[
      'methods' => 'GET',
      'callback' => [$this, 'show'],
      'permission_callback' => [$this, 'permissions'],
    ], [
      'methods' => 'PUT',
      'callback' => [$this, 'update'],
      'permission_callback' => [$this, 'permissions'],
    ]]);
    register_rest_route('sbay/v1', self::ROUTE . '/cleanup', [[
      'methods' => 'POST',
      'callback' => [$this, 'cleanup'],
      'permission_callback' => [$this, 'permissions'],
    ]]);
  }

  public function permissions(): bool|WP_Error {
    if (! is_user_logged_in()) {
      return new WP_Error('sbay_authentication_required', 'Authentication is required.', ['status' => 401]);
    }

    return current_user_can(CapabilityManager::MANAGE_SETTINGS)
      ? true
      : new WP_Error('sbay_permission_denied', 'You are not allowed to manage notification retention.', ['status' => 403]);
  }

  public function show(WP_REST_Request $request): WP_REST_Response {
    return RestResponse::success($this->retention->get()->toArray(), 'Notification retention retrieved.');
  }

  public function update(WP_REST_Request $request): WP_REST_Response {
    try {
      return RestResponse::success(
        $this->retention->update((array) $request->get_json_params())->toArray(),
        'Notification retention saved.',
      );
    } catch (InvalidArgumentException $exception) {
      return RestResponse::error($exception->getMessage(), 'INVALID_NOTIFICATION_RETENTION', [], 422);
    }
  }

  public function cleanup(WP_REST_Request $request): WP_REST_Response {
    return RestResponse::success($this->retention->cleanup(), 'Notification cleanup completed.');
  }
}
