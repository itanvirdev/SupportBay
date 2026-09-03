<?php

declare(strict_types=1);

namespace SupportBay\Dev;

use SupportBay\Core\Testing\Assert;
use SupportBay\Core\Testing\FlowTest;
use SupportBay\Modules\Notifications\Data\NotificationData;
use SupportBay\Modules\Notifications\Services\NotificationService;

final class NotificationFlowTest extends FlowTest {
  protected static function title(): string {
    return 'Notification Flow Test';
  }

  protected static function execute(...$services): void {
    /** @var NotificationService $notifications */
    [$notifications] = $services;
    $deliveries = [];
    $capture = static function (null|bool $return, array $attributes) use (&$deliveries): bool {
      $deliveries[] = $attributes;
      return true;
    };
    add_filter('pre_wp_mail', $capture, 10, 2);
    try {
      Assert::true($notifications->enqueue(new NotificationData(
        event: 'flow_test',
        recipient: 'supportbay-notification@example.com',
        subject: 'Notification flow test',
        content: '<p>Delivered through WordPress.</p>',
        headers: ['Content-Type: text/html; charset=UTF-8'],
      )), 'Notifications are delivered directly through the WordPress email channel.');
      Assert::count(1, $deliveries, 'Direct delivery invokes WordPress mail exactly once.');
      Assert::false($notifications->send(new NotificationData(
        event: 'flow_test', recipient: 'invalid', subject: 'Invalid', content: 'Invalid recipient.',
      )), 'Invalid recipients fail safely without creating delivery records.');
      Assert::count(1, $deliveries, 'Invalid recipients never reach WordPress mail.');
    } finally {
      remove_filter('pre_wp_mail', $capture, 10);
    }
  }
}
