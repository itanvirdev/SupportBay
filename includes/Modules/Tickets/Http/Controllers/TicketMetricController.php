<?php

declare(strict_types=1);

namespace SupportBay\Modules\Tickets\Http\Controllers;

use DateTimeImmutable;
use InvalidArgumentException;
use SupportBay\Core\Authorization\CapabilityManager;
use SupportBay\Core\Http\RestResponse;
use SupportBay\Modules\Tickets\Data\TicketMetricQuery;
use SupportBay\Modules\Tickets\Services\TicketMetricService;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

final class TicketMetricController {
  private const ROUTE = '/reports/tickets';

  public function __construct(private readonly TicketMetricService $metrics) {
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
      : new WP_Error('sbay_permission_denied', 'You are not allowed to view ticket reports.', ['status' => 403]);
  }

  public function show(WP_REST_Request $request): WP_REST_Response {
    try {
      return RestResponse::success($this->metrics->report($this->query($request)), 'Ticket performance report retrieved.');
    } catch (InvalidArgumentException $exception) {
      return RestResponse::error($exception->getMessage(), 'INVALID_TICKET_REPORT', [], 422);
    }
  }

  public function export(WP_REST_Request $request): WP_REST_Response {
    try {
      $query = $this->query($request);
      return RestResponse::success([
        'content' => $this->metrics->export($query),
        'filename' => sprintf('supportbay-ticket-report-%s-to-%s.csv', $query->dateFrom, $query->dateTo),
      ], 'Ticket report export created.');
    } catch (InvalidArgumentException $exception) {
      return RestResponse::error($exception->getMessage(), 'INVALID_TICKET_REPORT', [], 422);
    }
  }

  public function serveExport(bool $served, mixed $result, WP_REST_Request $request, WP_REST_Server $server): bool {
    if ($served || $request->get_route() !== '/sbay/v1/reports/tickets/export' || ! $result instanceof WP_REST_Response || $result->get_status() !== 200) {
      return $served;
    }
    $data = $result->get_data();
    $content = (string) ($data['data']['content'] ?? '');
    $filename = sanitize_file_name((string) ($data['data']['filename'] ?? 'supportbay-ticket-report.csv'));
    while (ob_get_level() > 0) { ob_end_clean(); }
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Length: ' . strlen($content));
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: private, no-store, max-age=0');
    header('X-Content-Type-Options: nosniff');
    echo $content;
    return true;
  }

  private function query(WP_REST_Request $request): TicketMetricQuery {
    $today = new DateTimeImmutable(current_time('Y-m-d'));
    $category = sanitize_text_field(
      (string) $request->get_param('category_id')
    );

    return new TicketMetricQuery(
      dateFrom: sanitize_text_field((string) $request->get_param('date_from')) ?: $today->modify('-29 days')->format('Y-m-d'),
      dateTo: sanitize_text_field((string) $request->get_param('date_to')) ?: $today->format('Y-m-d'),
      departmentId: absint($request->get_param('department_id')) ?: null,
      categoryId: $category !== 'uncategorized'
        ? (absint($category) ?: null)
        : null,
      uncategorized: $category === 'uncategorized',
      assignedAgentId: absint($request->get_param('assigned_agent_id')) ?: null,
      priority: sanitize_key((string) $request->get_param('priority')) ?: null,
    );
  }
}
