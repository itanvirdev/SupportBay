<?php

declare(strict_types=1);

namespace SupportBay\Modules\Notifications\Http\Controllers;

use InvalidArgumentException;
use SupportBay\Core\Authorization\CapabilityManager;
use SupportBay\Core\Http\RestResponse;
use SupportBay\Modules\Notifications\Services\NotificationPreferenceService;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class NotificationPreferenceController {
  private const ROUTE = '/admin/notification-preferences';

  public function __construct(
    private readonly NotificationPreferenceService $preferences,
  ) {
  }

  public function registerRoutes(): void {
    register_rest_route('sbay/v1', self::ROUTE, [
      [
        'methods' => 'GET',
        'callback' => [$this, 'show'],
        'permission_callback' => [$this, 'permissions'],
      ],
      [
        'methods' => 'PUT',
        'callback' => [$this, 'update'],
        'permission_callback' => [$this, 'permissions'],
      ],
    ]);
  }

  public function permissions(): bool|WP_Error {
    if (! is_user_logged_in()) {
      return new WP_Error(
        'sbay_authentication_required',
        'Authentication is required.',
        ['status' => 401],
      );
    }

    return current_user_can(CapabilityManager::MANAGE_SETTINGS)
      ? true
      : new WP_Error(
        'sbay_permission_denied',
        'You are not allowed to manage notification preferences.',
        ['status' => 403],
      );
  }

  public function show(WP_REST_Request $request): WP_REST_Response {
    return RestResponse::success(
      $this->preferences->get()->toArray(),
      'Notification preferences retrieved.',
    );
  }

  public function update(WP_REST_Request $request): WP_REST_Response {
    try {
      return RestResponse::success(
        $this->preferences->update([
          'enabled' => $request->has_param('enabled')
            ? $request->get_param('enabled')
            : $this->preferences->get()->enabled(),
          'events' => $request->has_param('events')
            ? $request->get_param('events')
            : $this->preferences->get()->events(),
        ])->toArray(),
        'Notification preferences saved.',
      );
    } catch (InvalidArgumentException $exception) {
      return RestResponse::error(
        $exception->getMessage(),
        'INVALID_NOTIFICATION_PREFERENCES',
        [],
        422,
      );
    }
  }
}
