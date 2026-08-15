<?php

declare(strict_types=1);

namespace SupportBay\Dev;

use SupportBay\Core\Testing\Assert;
use SupportBay\Core\Testing\FlowTest;
use SupportBay\Modules\Notifications\Enums\NotificationRecipientType;
use SupportBay\Modules\Notifications\Services\NotificationPreferenceService;

final class NotificationPreferenceFlowTest extends FlowTest {
  protected static function title(): string {
    return 'Notification Preference Flow Test';
  }

  protected static function execute(...$services): void {
    /** @var NotificationPreferenceService $preferences */
    [$preferences] = $services;
    $existing = get_option('sbay_notification_preferences', null);
    delete_option('sbay_notification_preferences');

    Assert::true(
      $preferences->allows(
        'ticket_created',
        NotificationRecipientType::CUSTOMER,
      )
      && $preferences->allows(
        'ticket_closed',
        NotificationRecipientType::CUSTOMER,
      )
      && $preferences->allows(
        'ticket_reopened',
        NotificationRecipientType::CUSTOMER,
      )
      && $preferences->allows(
        'ticket_resolved',
        NotificationRecipientType::CUSTOMER,
      )
      && $preferences->allows(
        'ticket_assigned',
        NotificationRecipientType::AGENT,
      )
      && $preferences->allows(
        'ticket_reassigned',
        NotificationRecipientType::AGENT,
      ),
      'Default preferences allow every built-in notification variant.'
    );

    $updated = $preferences->update([
      'events' => [
        'ticket_created' => ['customer' => false],
      ],
    ]);

    Assert::true(
      ! $updated->allows(
        'ticket_created',
        NotificationRecipientType::CUSTOMER,
      )
      && $updated->allows(
        'ticket_created',
        NotificationRecipientType::AGENT,
      ),
      'A recipient variant can be disabled without changing its sibling.'
    );

    $disabled = $preferences->update(['enabled' => false]);
    Assert::true(
      ! $disabled->allows(
        'ticket_created',
        NotificationRecipientType::AGENT,
      ),
      'The master switch suppresses every notification variant.'
    );

    if ($existing === null) {
      delete_option('sbay_notification_preferences');
    } else {
      update_option('sbay_notification_preferences', $existing, false);
    }
  }
}
