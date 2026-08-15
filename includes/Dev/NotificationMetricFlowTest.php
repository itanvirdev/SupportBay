<?php

declare(strict_types=1);

namespace SupportBay\Dev;

use InvalidArgumentException;
use SupportBay\Core\Testing\Assert;
use SupportBay\Core\Testing\FlowTest;
use SupportBay\Modules\Notifications\Data\NotificationMetricQuery;
use SupportBay\Modules\Notifications\Enums\NotificationStatus;
use SupportBay\Modules\Notifications\Http\Controllers\NotificationMetricController;
use SupportBay\Modules\Notifications\Repositories\NotificationLogRepository;
use SupportBay\Modules\Notifications\Services\NotificationMetricService;
use WP_Error;
use WP_REST_Request;

final class NotificationMetricFlowTest extends FlowTest {
  protected static function title(): string {
    return 'Notification Metric Flow Test';
  }

  protected static function execute(...$services): void {
    /** @var NotificationMetricService $metrics */
    /** @var NotificationMetricController $controller */
    /** @var NotificationLogRepository $logs */
    [$metrics, $controller, $logs] = $services;
    $today = current_time('Y-m-d');
    $event = 'metric_flow_' . strtolower(wp_generate_password(8, false, false));
    $recipient = $event . '@example.test';

    foreach ([
      [NotificationStatus::SENT, 0],
      [NotificationStatus::DELIVERED, 1],
      [NotificationStatus::FAILED, 2],
      [NotificationStatus::PENDING, 0],
    ] as [$status, $retries]) {
      $logs->create([
        'channel' => 'email',
        'event' => $event,
        'recipient' => $recipient,
        'subject' => 'Metric flow',
        'status' => $status->value,
        'retry_count' => $retries,
      ]);
    }

    try {
      $report = $metrics->report(new NotificationMetricQuery(
        dateFrom: $today,
        dateTo: $today,
        channel: 'email',
        event: $event,
      ));

      Assert::true(
        $report['summary']['total'] === 4
        && $report['summary']['successful'] === 2
        && $report['summary']['failed'] === 1
        && $report['summary']['queued'] === 1
        && $report['summary']['retries'] === 3
        && $report['summary']['success_rate'] === 50.0
        && $report['summary']['failure_rate'] === 25.0,
        'Filtered notification summary and rates are derived from audit records.',
      );
      Assert::true(
        count($report['daily']) === 1
        && $report['daily'][0]['total'] === 4
        && $report['events'][0]['event'] === $event
        && $report['channels'][0]['channel'] === 'email',
        'Daily, event, and channel breakdowns use the same report filters.',
      );

      try {
        $metrics->report(new NotificationMetricQuery('2026-12-31', '2026-01-01'));
        Assert::true(false, 'Invalid report ranges must be rejected.');
      } catch (InvalidArgumentException) {
        Assert::true(true, 'Invalid report ranges are rejected.');
      }

      if (did_action('rest_api_init') === 0) {
        do_action('rest_api_init', rest_get_server());
      }
      Assert::true(
        isset(rest_get_server()->get_routes()['/sbay/v1/reports/notifications']),
        'Notification report route is registered.',
      );
      wp_set_current_user(0);
      Assert::true($controller->permissions() instanceof WP_Error, 'Anonymous report access is rejected.');
      wp_set_current_user(1);
      Assert::true($controller->permissions() === true, 'Authorized administrators can view reports.');

      $request = new WP_REST_Request('GET', '/sbay/v1/reports/notifications');
      $request->set_query_params([
        'date_from' => $today,
        'date_to' => $today,
        'channel' => 'email',
        'event' => $event,
      ]);
      $response = rest_do_request($request);
      $data = $response->get_data();
      Assert::true(
        $response->get_status() === 200
        && ($data['data']['summary']['total'] ?? 0) === 4
        && ! str_contains(wp_json_encode($data), $recipient),
        'Protected report API returns aggregate data without recipients.',
      );
    } finally {
      $logs->deleteByRecipient($recipient);
      wp_set_current_user(0);
    }
  }
}
