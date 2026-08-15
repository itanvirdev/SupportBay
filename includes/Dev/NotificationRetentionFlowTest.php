<?php

declare(strict_types=1);

namespace SupportBay\Dev;

use InvalidArgumentException;
use SupportBay\Core\Testing\Assert;
use SupportBay\Core\Testing\FlowTest;
use SupportBay\Modules\Notifications\Enums\NotificationStatus;
use SupportBay\Modules\Notifications\Repositories\NotificationLogRepository;
use SupportBay\Modules\Notifications\Services\NotificationRetentionService;

final class NotificationRetentionFlowTest extends FlowTest {
  protected static function title(): string {
    return 'Notification Retention Flow Test';
  }

  protected static function execute(...$services): void {
    /** @var NotificationRetentionService $retention */
    /** @var NotificationLogRepository $logs */
    [$retention, $logs] = $services;
    $existing = get_option('sbay_notification_retention', null);
    delete_option('sbay_notification_retention');
    $recipient = 'retention-flow-' . wp_generate_uuid4() . '@example.test';

    try {
      Assert::true(
        $retention->get()->enabled()
        && $retention->get()->retentionDays() === 90
        && $retention->get()->batchSize() === 500,
        'Retention defaults are enabled and bounded.',
      );

      $retention->update([
        'enabled' => true,
        'retention_days' => 7,
        'batch_size' => 50,
      ]);

      foreach ([
        [NotificationStatus::SENT, 0],
        [NotificationStatus::FAILED, 3],
        [NotificationStatus::FAILED, 2],
        [NotificationStatus::PENDING, 0],
      ] as [$status, $retries]) {
        $logs->create([
          'channel' => 'email',
          'event' => 'retention_flow',
          'recipient' => $recipient,
          'subject' => 'Retention safety',
          'status' => $status->value,
          'retry_count' => $retries,
          'created_at' => '2000-01-01 00:00:00',
          'updated_at' => '2000-01-01 00:00:00',
        ]);
      }

      $result = $retention->cleanup();
      $remaining = $logs->search(new \SupportBay\Modules\Notifications\Data\NotificationLogQuery(
        search: $recipient,
        page: 1,
        perPage: 20,
      ));

      Assert::true(
        $result['deleted'] === 2 && $remaining['total'] === 2,
        'Cleanup removes successful and exhausted records but preserves active records.',
      );

      $statuses = array_map(
        static fn($log): string => $log->status()->value,
        $remaining['items'],
      );
      Assert::true(
        in_array(NotificationStatus::PENDING->value, $statuses, true)
        && in_array(NotificationStatus::FAILED->value, $statuses, true),
        'Pending and retry-eligible failed notifications remain available.',
      );

      try {
        $retention->update(['retention_days' => 1]);
        Assert::true(false, 'Unsafe retention periods must be rejected.');
      } catch (InvalidArgumentException) {
        Assert::true(true, 'Unsafe retention periods are rejected.');
      }
    } finally {
      $logs->deleteByRecipient($recipient);

      if ($existing === null) {
        delete_option('sbay_notification_retention');
      } else {
        update_option('sbay_notification_retention', $existing, false);
      }
    }
  }
}
