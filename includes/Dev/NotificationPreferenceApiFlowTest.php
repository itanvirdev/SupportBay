<?php

declare(strict_types=1);

namespace SupportBay\Dev;

use SupportBay\Core\Testing\Assert;
use SupportBay\Core\Testing\FlowTest;
use SupportBay\Modules\Notifications\Http\Controllers\NotificationPreferenceController;
use WP_Error;
use WP_REST_Request;

final class NotificationPreferenceApiFlowTest extends FlowTest {
  protected static function title(): string {
    return 'Notification Preference API Flow Test';
  }

  protected static function execute(...$services): void {
    /** @var NotificationPreferenceController $controller */
    [$controller] = $services;

    if (did_action('rest_api_init') === 0) {
      do_action('rest_api_init', rest_get_server());
    }

    Assert::true(
      isset(rest_get_server()->get_routes()['/sbay/v1/admin/notification-preferences']),
      'Administrator notification preference route is registered.'
    );

    wp_set_current_user(0);
    Assert::true(
      $controller->permissions() instanceof WP_Error,
      'Anonymous preference access is rejected.'
    );

    $existing = get_option('sbay_notification_preferences', null);
    delete_option('sbay_notification_preferences');
    wp_set_current_user(1);

    $show = rest_do_request(new WP_REST_Request(
      'GET',
      '/sbay/v1/admin/notification-preferences',
    ));
    Assert::true(
      $show->get_status() === 200
      && ($show->get_data()['data']['enabled'] ?? false) === true
      && ($show->get_data()['data']['events']['ticket_created']['agent'] ?? false) === true
      && ($show->get_data()['data']['events']['ticket_closed']['customer'] ?? false) === true
      && ($show->get_data()['data']['events']['ticket_reopened']['customer'] ?? false) === true
      && ($show->get_data()['data']['events']['ticket_resolved']['customer'] ?? false) === true
      && ($show->get_data()['data']['events']['ticket_assigned']['agent'] ?? false) === true
      && ($show->get_data()['data']['events']['ticket_reassigned']['agent'] ?? false) === true,
      'Administrators receive complete safe defaults.'
    );

    $request = new WP_REST_Request(
      'PUT',
      '/sbay/v1/admin/notification-preferences',
    );
    $request->set_body_params([
      'enabled' => true,
      'events' => ['ticket_created' => ['agent' => false]],
    ]);
    $update = rest_do_request($request);
    Assert::true(
      $update->get_status() === 200
      && ($update->get_data()['data']['events']['ticket_created']['agent'] ?? true) === false
      && ($update->get_data()['data']['events']['ticket_created']['customer'] ?? false) === true,
      'Partial event updates preserve omitted recipient preferences.'
    );

    $invalid = new WP_REST_Request(
      'PUT',
      '/sbay/v1/admin/notification-preferences',
    );
    $invalid->set_body_params([
      'events' => ['unknown_event' => ['agent' => false]],
    ]);
    Assert::equals(
      422,
      rest_do_request($invalid)->get_status(),
      'Unknown events are rejected.'
    );

    if ($existing === null) {
      delete_option('sbay_notification_preferences');
    } else {
      update_option('sbay_notification_preferences', $existing, false);
    }
    wp_set_current_user(0);
  }
}
