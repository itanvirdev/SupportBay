<?php

declare(strict_types=1);

namespace SupportBay\Modules\Notifications\Services;

final class NotificationCleanupWorker {
  public const HOOK = 'sbay_notification_cleanup';

  public function __construct(
    private readonly NotificationRetentionService $retention,
  ) {
  }

  public function register(): void {
    add_action(self::HOOK, [$this, 'run']);
    add_action('init', [$this, 'ensureScheduled'], 20);
  }

  public function ensureScheduled(): void {
    if (wp_next_scheduled(self::HOOK) === false) {
      wp_schedule_event(
        (int) current_time('timestamp', true) + DAY_IN_SECONDS,
        'daily',
        self::HOOK,
      );
    }
  }

  /** @return array{deleted: int, cutoff: string} */
  public function run(): array {
    return $this->retention->cleanup();
  }
}
