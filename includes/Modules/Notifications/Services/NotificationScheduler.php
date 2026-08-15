<?php

declare(strict_types=1);

namespace SupportBay\Modules\Notifications\Services;

final class NotificationScheduler {
  public const DISPATCH_HOOK = 'sbay_notification_dispatch';
  public const RETRY_HOOK = 'sbay_notification_retry';
  public const SCHEDULE = 'sbay_every_five_minutes';

  private const INTERVAL_SECONDS = 300;

  public function register(): void {
    add_filter('cron_schedules', [$this, 'schedules']);
    add_action('init', [$this, 'ensureRetryScheduled'], 20);
  }

  /** @param array<string, array<string, int|string>> $schedules */
  public function schedules(array $schedules): array {
    $schedules[self::SCHEDULE] = [
      'interval' => self::INTERVAL_SECONDS,
      'display' => did_action('init') > 0
        ? __('Every five minutes', 'supportbay')
        : 'Every five minutes',
    ];

    return $schedules;
  }

  public function ensureRetryScheduled(): void {
    if (wp_next_scheduled(self::RETRY_HOOK) !== false) {
      return;
    }

    wp_schedule_event(
      $this->now() + self::INTERVAL_SECONDS,
      self::SCHEDULE,
      self::RETRY_HOOK,
    );
  }

  public function scheduleDispatch(): void {
    wp_schedule_single_event(
      $this->now() + 1,
      self::DISPATCH_HOOK,
    );
  }

  private function now(): int {
    return (int) current_time('timestamp', true);
  }
}
