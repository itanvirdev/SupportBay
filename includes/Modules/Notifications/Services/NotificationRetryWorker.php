<?php

declare(strict_types=1);

namespace SupportBay\Modules\Notifications\Services;

final class NotificationRetryWorker {
  public const HOOK = NotificationScheduler::RETRY_HOOK;
  public const SCHEDULE = NotificationScheduler::SCHEDULE;

  private const BATCH_SIZE = 20;

  public function __construct(
    private readonly NotificationService $notifications,
    private readonly NotificationScheduler $scheduler,
  ) {
  }

  public function register(): void {
    add_action(self::HOOK, [$this, 'run']);
    add_action(NotificationScheduler::DISPATCH_HOOK, [$this, 'runPending']);
  }

  public function ensureScheduled(): void {
    $this->scheduler->ensureRetryScheduled();
  }

  /** @return array{processed: int, sent: int, failed: int} */
  public function run(?string $now = null): array {
    return $this->notifications->retryDue(self::BATCH_SIZE, $now);
  }

  /** @return array{processed: int, sent: int, failed: int} */
  public function runPending(?string $now = null): array {
    $result = $this->notifications->dispatchDue(
      self::BATCH_SIZE,
      $now,
    );

    if ($result['processed'] === self::BATCH_SIZE) {
      $this->scheduler->scheduleDispatch();
    }

    return $result;
  }
}
