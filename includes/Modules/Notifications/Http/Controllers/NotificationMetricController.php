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
use WP_REST_Server;

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
    register_rest_route('sbay/v1', self::ROUTE . '/export', [
      'methods' => 'GET',
      'callback' => [$this, 'export'],
      'permission_callback' => [$this, 'exportPermissions'],
    ]);
    add_filter('rest_pre_serve_request', [$this, 'serveExport'], 10, 4);
  }

  public function exportPermissions(): bool|WP_Error {
    if (! is_user_logged_in()) {
      return new WP_Error('sbay_authentication_required', 'Authentication is required.', ['status' => 401]);
    }
    return current_user_can(CapabilityManager::EXPORT_REPORTS)
      ? true
      : new WP_Error('sbay_permission_denied', 'You are not allowed to export reports.', ['status' => 403]);
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
    try {
      $report = $this->metrics->report($this->query($request));

      return RestResponse::success($report, 'Notification delivery report retrieved.');
    } catch (InvalidArgumentException $exception) {
      return RestResponse::error($exception->getMessage(), 'INVALID_NOTIFICATION_REPORT', [], 422);
    }
  }

  public function export(WP_REST_Request $request): WP_REST_Response {
    try {
      $query = $this->query($request);
      return RestResponse::success([
        'content' => $this->metrics->export($query),
        'filename' => sprintf('supportbay-notification-report-%s-to-%s.csv', $query->dateFrom, $query->dateTo),
      ], 'Notification report export created.');
    } catch (InvalidArgumentException $exception) {
      return RestResponse::error($exception->getMessage(), 'INVALID_NOTIFICATION_REPORT', [], 422);
    }
  }

  public function serveExport(bool $served, mixed $result, WP_REST_Request $request, WP_REST_Server $server): bool {
    if ($served || $request->get_route() !== '/sbay/v1/reports/notifications/export' || ! $result instanceof WP_REST_Response || $result->get_status() !== 200) {
      return $served;
    }
    $data = $result->get_data();
    $content = (string) ($data['data']['content'] ?? '');
    $filename = sanitize_file_name((string) ($data['data']['filename'] ?? 'supportbay-notification-report.csv'));
    while (ob_get_level() > 0) { ob_end_clean(); }
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Length: ' . strlen($content));
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: private, no-store, max-age=0');
    header('X-Content-Type-Options: nosniff');
    echo $content;
    return true;
  }

  private function query(WP_REST_Request $request): NotificationMetricQuery {
    $today = new DateTimeImmutable(current_time('Y-m-d'));
    return new NotificationMetricQuery(
      dateFrom: sanitize_text_field((string) $request->get_param('date_from')) ?: $today->modify('-29 days')->format('Y-m-d'),
      dateTo: sanitize_text_field((string) $request->get_param('date_to')) ?: $today->format('Y-m-d'),
      channel: sanitize_key((string) $request->get_param('channel')) ?: null,
      event: sanitize_key((string) $request->get_param('event')) ?: null,
    );
  }
}
