<?php

declare(strict_types=1);

namespace SupportBay\Dev;

use SupportBay\Core\Testing\Assert;
use SupportBay\Core\Testing\FlowTest;
use SupportBay\Modules\Notifications\Data\NotificationData;
use SupportBay\Modules\Notifications\Enums\NotificationStatus;
use SupportBay\Modules\Notifications\Repositories\NotificationLogRepository;
use SupportBay\Modules\Notifications\Services\NotificationRetryWorker;
use SupportBay\Modules\Notifications\Services\NotificationService;

final class NotificationRetryFlowTest extends FlowTest {
  protected static function title(): string {
    return 'Scheduled Notification Retry Flow Test';
  }

  protected static function execute(...$services): void {
    /** @var NotificationRetryWorker $worker */
    /** @var NotificationService $notifications */
    /** @var NotificationLogRepository $logs */
    [$worker, $notifications, $logs] = $services;

    $worker->ensureScheduled();
    $schedules = wp_get_schedules();

    Assert::true(
      isset($schedules[NotificationRetryWorker::SCHEDULE])
      && wp_next_scheduled(NotificationRetryWorker::HOOK) !== false,
      'The recurring notification retry worker is registered and scheduled.'
    );

    $deliverySucceeds = true;
    $capture = static function (
      null|bool $return,
      array $attributes,
    ) use (&$deliverySucceeds): bool {
      return $deliverySucceeds;
    };
    add_filter('pre_wp_mail', $capture, 10, 2);

    $suffix = strtolower(wp_generate_password(10, false, false));
    $ticketId = 910000000 + wp_rand(1, 9999999);

    $queuedId = $notifications->enqueue(new NotificationData(
      event: 'system_alert',
      recipient: 'queued-delivery-' . $suffix . '@example.com',
      subject: 'Queued delivery test',
      content: 'This delivery should be sent by the queue worker.',
      metadata: ['ticket_id' => $ticketId],
    ));
    $logs->schedulePending($queuedId, '2000-01-01 00:00:00');

    Assert::equals(
      ['processed' => 1, 'sent' => 1, 'failed' => 0],
      $worker->runPending('2000-01-01 00:00:00'),
      'The worker delivers due queued mail without consuming a retry.'
    );

    $queued = $logs->find($queuedId);

    Assert::true(
      $queued !== null
      && $queued->status() === NotificationStatus::SENT
      && $queued->retryCount() === 0,
      'Initial queued delivery remains retry count zero.'
    );

    $deliverySucceeds = false;

    Assert::false(
      $notifications->send(new NotificationData(
        event: 'system_alert',
        recipient: 'scheduled-retry-' . $suffix . '@example.com',
        subject: 'Scheduled retry test',
        content: 'This delivery should be retried by the cron worker.',
        metadata: ['ticket_id' => $ticketId],
      )),
      'A channel failure creates an automatically scheduled retry.'
    );

    $ticketLogs = $logs->findByTicket($ticketId);
    $failed = $ticketLogs[count($ticketLogs) - 1] ?? null;

    Assert::true(
      $failed !== null
      && $failed->status() === NotificationStatus::FAILED
      && $failed->scheduledAt() !== null,
      'The failed notification records its future retry time.'
    );

    $notifications->retryDue();
    $notDue = $logs->find($failed->id());
    Assert::true(
      $notDue !== null
      && $notDue->status() === NotificationStatus::FAILED
      && $notDue->retryCount() === 0
      && $notDue->scheduledAt() !== null,
      'The worker leaves this notification unchanged before it is due.'
    );

    $logs->markFailed(
      $failed->id(),
      (string) $failed->errorMessage(),
      '2000-01-01 00:00:00',
    );
    $deliverySucceeds = true;
    $result = $worker->run('2000-01-01 00:00:00');
    $retried = $logs->find($failed->id());

    Assert::true(
      $result['processed'] >= 1
      && $retried !== null
      && $retried->status() === NotificationStatus::SENT
      && $retried->retryCount() === 1
      && $retried->scheduledAt() === null,
      'The worker atomically retries due delivery and clears its schedule.'
    );

    remove_filter('pre_wp_mail', $capture, 10);
    Assert::equals(
      2,
      $logs->deleteByTicket($ticketId),
      'Scheduled notification retry test log deleted.'
    );
  }
}
