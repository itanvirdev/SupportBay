<?php

declare(strict_types=1);

namespace SupportBay\Modules\Notifications\Http\Controllers;

use DateTimeImmutable;
use InvalidArgumentException;
use SupportBay\Core\Authorization\CapabilityManager;
use SupportBay\Core\Http\RestResponse;
use SupportBay\Modules\Notifications\Data\NotificationMetricQuery;
use SupportBay\Modules\Notifications\Services\NotificationMetricService;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class NotificationMetricController {
  private const ROUTE = '/reports/notifications';

  public function __construct(
    private readonly NotificationMetricService $metrics,
  ) {
  }

  public function registerRoutes(): void {
    register_rest_route('sbay/v1', self::ROUTE, [
      'methods' => 'GET',
      'callback' => [$this, 'show'],
      'permission_callback' => [$this, 'permissions'],
    ]);
  }

  public function permissions(): bool|WP_Error {
    if (! is_user_logged_in()) {
      return new WP_Error('sbay_authentication_required', 'Authentication is required.', ['status' => 401]);
    }

    return current_user_can(CapabilityManager::VIEW_REPORTS)
      ? true
      : new WP_Error('sbay_permission_denied', 'You are not allowed to view notification reports.', ['status' => 403]);
  }

  public function show(WP_REST_Request $request): WP_REST_Response {
    $today = new DateTimeImmutable(current_time('Y-m-d'));

    try {
      $report = $this->metrics->report(new NotificationMetricQuery(
        dateFrom: sanitize_text_field((string) $request->get_param('date_from')) ?: $today->modify('-29 days')->format('Y-m-d'),
        dateTo: sanitize_text_field((string) $request->get_param('date_to')) ?: $today->format('Y-m-d'),
        channel: sanitize_key((string) $request->get_param('channel')) ?: null,
        event: sanitize_key((string) $request->get_param('event')) ?: null,
      ));

      return RestResponse::success($report, 'Notification delivery report retrieved.');
    } catch (InvalidArgumentException $exception) {
      return RestResponse::error($exception->getMessage(), 'INVALID_NOTIFICATION_REPORT', [], 422);
    }
  }
}
