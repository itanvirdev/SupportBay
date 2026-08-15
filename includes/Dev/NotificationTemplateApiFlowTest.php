<?php

declare(strict_types=1);

namespace SupportBay\Dev;

use SupportBay\Core\Testing\Assert;
use SupportBay\Core\Testing\FlowTest;
use SupportBay\Modules\Notifications\Http\Controllers\NotificationTemplateController;
use SupportBay\Modules\Notifications\Repositories\NotificationLogRepository;
use WP_Error;
use WP_REST_Request;

final class NotificationTemplateApiFlowTest extends FlowTest {
  protected static function title(): string {
    return 'Notification Template API Flow Test';
  }

  protected static function execute(...$services): void {
    /** @var NotificationTemplateController $controller */
    /** @var NotificationLogRepository $logs */
    [$controller, $logs] = $services;

    if (did_action('rest_api_init') === 0) {
      do_action('rest_api_init', rest_get_server());
    }

    $routes = rest_get_server()->get_routes();
    Assert::true(
      isset($routes['/sbay/v1/admin/notification-templates'])
      && isset($routes['/sbay/v1/admin/notification-templates/(?P<event>[a-z0-9_-]+)/(?P<recipient>[a-z]+)'])
      && isset($routes['/sbay/v1/admin/notification-templates/(?P<event>[a-z0-9_-]+)/(?P<recipient>[a-z]+)/reset'])
      && isset($routes['/sbay/v1/admin/notification-templates/(?P<event>[a-z0-9_-]+)/(?P<recipient>[a-z]+)/preview'])
      && isset($routes['/sbay/v1/admin/notification-templates/(?P<event>[a-z0-9_-]+)/(?P<recipient>[a-z]+)/test-email']),
      'Administrator notification template routes are registered.'
    );

    wp_set_current_user(0);
    Assert::true(
      $controller->permissions() instanceof WP_Error,
      'Anonymous template access is rejected.'
    );

    $subscriberId = wp_insert_user([
      'user_login' => 'sbay-template-api-' . strtolower(
        wp_generate_password(10, false, false)
      ),
      'user_pass' => wp_generate_password(32, true, true),
      'role' => 'subscriber',
    ]);
    wp_set_current_user((int) $subscriberId);
    Assert::true(
      $controller->permissions() instanceof WP_Error,
      'Non-administrator template access is rejected.'
    );

    $existing = get_option('sbay_notification_templates', null);
    delete_option('sbay_notification_templates');
    wp_set_current_user(1);

    $index = rest_do_request(new WP_REST_Request(
      'GET',
      '/sbay/v1/admin/notification-templates',
    ));
    $indexData = $index->get_data();

    Assert::true(
      $index->get_status() === 200
      && count($indexData['data'] ?? []) === 9
      && in_array('active', $indexData['meta']['statuses'] ?? [], true)
      && in_array('customer_name', $indexData['meta']['placeholders'] ?? [], true),
      'Administrators receive templates and editor metadata.'
    );

    $path = '/sbay/v1/admin/notification-templates/ticket_created/customer';
    $update = new WP_REST_Request('PUT', $path);
    $update->set_body_params([
      'subject' => 'Updated {{track_id}}',
      'html_content' => '<p>Safe</p><script>unsafe()</script>',
      'plain_text_content' => 'Ticket {{track_id}} updated.',
    ]);
    $updateResponse = rest_do_request($update);
    $updated = $updateResponse->get_data()['data'] ?? [];

    Assert::true(
      $updateResponse->get_status() === 200
      && ($updated['subject'] ?? '') === 'Updated {{track_id}}'
      && ! str_contains((string) ($updated['html_content'] ?? ''), '<script')
      && ($updated['status'] ?? '') === 'active',
      'Administrators can partially update sanitized template content.'
    );

    $show = rest_do_request(new WP_REST_Request('GET', $path));
    Assert::equals(
      'Updated {{track_id}}',
      $show->get_data()['data']['subject'] ?? '',
      'Saved template changes are returned by the detail endpoint.'
    );

    $previewRequest = new WP_REST_Request('POST', $path . '/preview');
    $previewRequest->set_body_params([
      'subject' => 'Preview {{customer_name}}',
      'html_content' => '<p>{{ticket_subject}}</p><script>unsafe()</script>',
      'plain_text_content' => 'Preview ticket {{track_id}}.',
    ]);
    $previewResponse = rest_do_request($previewRequest);
    $preview = $previewResponse->get_data()['data'] ?? [];

    Assert::true(
      $previewResponse->get_status() === 200
      && ($preview['subject'] ?? '') === 'Preview Alex Customer'
      && str_contains(
        (string) ($preview['html_content'] ?? ''),
        'Demo support request',
      )
      && ! str_contains(
        (string) ($preview['html_content'] ?? ''),
        '<script',
      ),
      'Draft preview uses sanitized content and server-owned sample data.'
    );

    $showAfterPreview = rest_do_request(new WP_REST_Request('GET', $path));
    Assert::equals(
      'Updated {{track_id}}',
      $showAfterPreview->get_data()['data']['subject'] ?? '',
      'Preview never persists draft content.'
    );

    $invalidTest = new WP_REST_Request('POST', $path . '/test-email');
    $invalidTest->set_body_params(['test_recipient' => 'not-an-email']);
    Assert::equals(
      422,
      rest_do_request($invalidTest)->get_status(),
      'Test email rejects invalid recipients before delivery.'
    );

    $deliveries = [];
    $capture = static function (
      null|bool $return,
      array $attributes,
    ) use (&$deliveries): bool {
      $deliveries[] = $attributes;

      return true;
    };
    add_filter('pre_wp_mail', $capture, 10, 2);
    $testRecipient = 'template-preview-' . strtolower(
      wp_generate_password(10, false, false)
    ) . '@example.com';
    $testRequest = new WP_REST_Request('POST', $path . '/test-email');
    $testRequest->set_body_params([
      'test_recipient' => $testRecipient,
      'subject' => 'Test {{track_id}}',
      'plain_text_content' => 'Hello {{customer_name}}',
    ]);
    $testResponse = rest_do_request($testRequest);

    Assert::true(
      $testResponse->get_status() === 200
      && ($testResponse->get_data()['data']['sent'] ?? false) === true
      && ($deliveries[0]['to'] ?? '') === $testRecipient
      && ($deliveries[0]['subject'] ?? '') === 'Test 54E5DF43'
      && ($deliveries[0]['message'] ?? '') === 'Hello Alex Customer',
      'Test email sends sanitized rendered draft through WordPress mail.'
    );
    remove_filter('pre_wp_mail', $capture, 10);
    Assert::equals(
      1,
      $logs->deleteByRecipient($testRecipient),
      'Test email delivery audit log deleted.'
    );

    $reset = rest_do_request(new WP_REST_Request('POST', $path . '/reset'));
    Assert::true(
      $reset->get_status() === 200
      && str_contains(
        (string) ($reset->get_data()['data']['subject'] ?? ''),
        'We received your ticket',
      ),
      'Administrators can restore a template default.'
    );

    $missing = rest_do_request(new WP_REST_Request(
      'GET',
      '/sbay/v1/admin/notification-templates/not_real/customer',
    ));
    Assert::equals(
      404,
      $missing->get_status(),
      'Unknown predefined templates return a safe not-found response.'
    );

    if ($existing === null) {
      delete_option('sbay_notification_templates');
    } else {
      update_option('sbay_notification_templates', $existing, false);
    }

    if (! function_exists('wp_delete_user')) {
      require_once ABSPATH . 'wp-admin/includes/user.php';
    }

    wp_delete_user((int) $subscriberId);
    wp_set_current_user(0);
  }
}
