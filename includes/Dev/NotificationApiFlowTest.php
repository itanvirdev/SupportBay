<?php

declare(strict_types=1);

namespace SupportBay\Dev;

use SupportBay\Core\Testing\Assert;
use SupportBay\Core\Testing\FlowTest;
use SupportBay\Modules\Notifications\Data\NotificationData;
use SupportBay\Modules\Notifications\Http\Controllers\NotificationController;
use SupportBay\Modules\Notifications\Repositories\NotificationLogRepository;
use SupportBay\Modules\Notifications\Services\NotificationService;
use WP_Error;
use WP_REST_Request;

final class NotificationApiFlowTest extends FlowTest {
  protected static function title(): string {
    return 'Notification API Flow Test';
  }

  protected static function execute(...$services): void {
    /** @var NotificationController $controller */
    /** @var NotificationService $notifications */
    /** @var NotificationLogRepository $logs */
    [$controller, $notifications, $logs] = $services;

    if (did_action('rest_api_init') === 0) {
      do_action('rest_api_init', rest_get_server());
    }

    $routes = rest_get_server()->get_routes();

    Assert::true(
      isset($routes['/sbay/v1/admin/notifications'])
      && isset($routes['/sbay/v1/admin/notifications/(?P<id>\d+)'])
      && isset($routes['/sbay/v1/admin/notifications/(?P<id>\d+)/retry']),
      'Administrator notification log and retry routes are registered.'
    );

    wp_set_current_user(0);
    Assert::true(
      $controller->permissions() instanceof WP_Error,
      'Anonymous notification log access is rejected.'
    );

    $subscriberId = wp_insert_user([
      'user_login' => 'sbay-notification-api-' . strtolower(
        wp_generate_password(10, false, false)
      ),
      'user_pass' => wp_generate_password(32, true, true),
      'role' => 'subscriber',
    ]);
    wp_set_current_user((int) $subscriberId);
    Assert::true(
      $controller->permissions() instanceof WP_Error,
      'Non-administrator notification log access is rejected.'
    );

    wp_set_current_user(1);
    Assert::true(
      $controller->permissions() === true,
      'Administrator notification log access is allowed.'
    );

    $deliverySucceeds = false;
    $capture = static function (
      null|bool $return,
      array $attributes,
    ) use (&$deliverySucceeds): bool {
      return $deliverySucceeds;
    };
    add_filter('pre_wp_mail', $capture, 10, 2);

    $suffix = strtolower(wp_generate_password(10, false, false));
    $ticketId = 900000000 + wp_rand(1, 9999999);
    $recipient = 'notification-api-' . $suffix . '@example.com';

    Assert::false(
      $notifications->send(new NotificationData(
        event: 'system_alert',
        recipient: $recipient,
        subject: 'Notification API retry test',
        content: 'Private content must never appear in the API response.',
        headers: ['X-SupportBay-Test: secret-header'],
        metadata: ['ticket_id' => $ticketId],
      )),
      'Notification API test failure is recorded.'
    );

    $indexRequest = new WP_REST_Request(
      'GET',
      '/sbay/v1/admin/notifications'
    );
    $indexRequest->set_query_params([
      'search' => $recipient,
      'status' => 'failed',
      'per_page' => 1,
    ]);
    $indexResponse = rest_do_request($indexRequest);
    $indexData = $indexResponse->get_data();
    $log = $indexData['data'][0] ?? [];
    $logId = (int) ($log['id'] ?? 0);

    Assert::true(
      $indexResponse->get_status() === 200
      && ($indexData['meta']['total'] ?? 0) === 1
      && ($log['status'] ?? '') === 'failed'
      && ($log['can_retry'] ?? false) === true,
      'Administrator can search and filter paginated notification failures.'
    );

    Assert::true(
      ! array_key_exists('payload', $log)
      && ! array_key_exists('metadata', $log)
      && ! str_contains(wp_json_encode($indexData), 'Private content')
      && ! str_contains(wp_json_encode($indexData), 'secret-header'),
      'Notification API never exposes stored content, headers, or raw metadata.'
    );

    $showResponse = rest_do_request(new WP_REST_Request(
      'GET',
      '/sbay/v1/admin/notifications/' . $logId,
    ));
    Assert::equals(
      200,
      $showResponse->get_status(),
      'Administrator can inspect notification delivery diagnostics.'
    );

    $deliverySucceeds = true;
    $retryResponse = rest_do_request(new WP_REST_Request(
      'POST',
      '/sbay/v1/admin/notifications/' . $logId . '/retry',
    ));
    $retryData = $retryResponse->get_data();

    Assert::true(
      $retryResponse->get_status() === 200
      && ($retryData['data']['status'] ?? '') === 'sent'
      && ($retryData['data']['retry_count'] ?? 0) === 1,
      'Administrator can retry a failed notification through the protected API.'
    );

    remove_filter('pre_wp_mail', $capture, 10);
    Assert::equals(
      1,
      $logs->deleteByTicket($ticketId),
      'Notification API test log deleted.'
    );
    if (! function_exists('wp_delete_user')) {
      require_once ABSPATH . 'wp-admin/includes/user.php';
    }

    wp_delete_user((int) $subscriberId);
    wp_set_current_user(0);
  }
}
