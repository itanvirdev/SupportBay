<?php

declare(strict_types=1);

namespace SupportBay\Modules\Notifications\Services;

use RuntimeException;
use Throwable;
use SupportBay\Modules\Notifications\Contracts\NotificationChannel;
use SupportBay\Modules\Notifications\Data\NotificationData;
use SupportBay\Modules\Notifications\Data\NotificationLogQuery;
use SupportBay\Modules\Notifications\Entities\NotificationLog;
use SupportBay\Modules\Notifications\Enums\NotificationStatus;
use SupportBay\Modules\Notifications\Repositories\NotificationLogRepository;

final class NotificationService {
  private const MAXIMUM_RETRY_ATTEMPTS = 3;
  private const RETRY_BASE_DELAY_SECONDS = 300;

  public function __construct(
    private readonly NotificationChannel $channel,
    private readonly NotificationLogRepository $logs,
    private readonly NotificationScheduler $scheduler,
  ) {
  }

  public function send(NotificationData $notification): bool {
    return $this->deliver($notification, $this->createLog($notification));
  }

  public function enqueue(NotificationData $notification): int {
    $logId = $this->createLog(
      $notification,
      current_time('mysql'),
    );
    $this->scheduler->scheduleDispatch();

    return $logId;
  }

  private function createLog(
    NotificationData $notification,
    ?string $scheduledAt = null,
  ): int {
    return $this->logs->create([
      'ticket_id' => $this->metadataId($notification, 'ticket_id'),
      'user_id' => $this->metadataId($notification, 'user_id'),
      'channel' => sanitize_key($this->channel->channel()),
      'event' => sanitize_key($notification->event()),
      'recipient' => sanitize_text_field($notification->recipient()),
      'subject' => sanitize_text_field($notification->subject()),
      'payload' => wp_json_encode([
        'content' => $notification->content(),
        'headers' => $notification->headers(),
      ]),
      'status' => NotificationStatus::PENDING->value,
      'scheduled_at' => $scheduledAt,
      'provider' => $this->channel->provider() !== null
        ? sanitize_key($this->channel->provider())
        : null,
      'metadata' => wp_json_encode($notification->metadata()),
    ]);
  }

  /**
   * Retry an existing notification audit record.
   */
  public function retry(int $logId): bool {
    $log = $this->logs->find($logId);

    if (! $log) {
      throw new RuntimeException('Notification log not found.');
    }

    if (! $log->canRetry(self::MAXIMUM_RETRY_ATTEMPTS)) {
      throw new RuntimeException(
        'This notification cannot be retried.'
      );
    }

    $this->assertCompatibleChannel($log);
    $notification = $this->notificationFromLog($log);

    if (! $this->logs->beginRetry(
      $logId,
      self::MAXIMUM_RETRY_ATTEMPTS,
    )) {
      throw new RuntimeException(
        'This notification retry was already claimed or exhausted.'
      );
    }

    return $this->deliver($notification, $logId);
  }

  public function dispatch(int $logId): bool {
    $log = $this->logs->find($logId);

    if (! $log || $log->status() !== NotificationStatus::PENDING) {
      throw new RuntimeException(
        'This notification is not pending dispatch.'
      );
    }

    $this->assertCompatibleChannel($log);
    $notification = $this->notificationFromLog($log);

    if (! $this->logs->beginDispatch($logId)) {
      throw new RuntimeException(
        'This notification dispatch was already claimed.'
      );
    }

    return $this->deliver($notification, $logId);
  }

  /** @return NotificationLog[] */
  public function logsForTicket(int $ticketId): array {
    return $this->logs->findByTicket($ticketId);
  }

  public function findLog(int $logId): ?NotificationLog {
    return $this->logs->find($logId);
  }

  /** @return array{items: NotificationLog[], total: int} */
  public function searchLogs(NotificationLogQuery $query): array {
    return $this->logs->search($query);
  }

  /** @return array{processed: int, sent: int, failed: int} */
  public function retryDue(
    int $limit = 20,
    ?string $now = null,
  ): array {
    $result = ['processed' => 0, 'sent' => 0, 'failed' => 0];
    $logs = $this->logs->findDueForRetry(
      $now ?? current_time('mysql'),
      self::MAXIMUM_RETRY_ATTEMPTS,
      $limit,
    );

    foreach ($logs as $log) {
      try {
        $sent = $this->retry($log->id());
      } catch (RuntimeException) {
        $sent = false;
      }

      $result['processed']++;
      $result[$sent ? 'sent' : 'failed']++;
    }

    return $result;
  }

  /** @return array{processed: int, sent: int, failed: int} */
  public function dispatchDue(
    int $limit = 20,
    ?string $now = null,
  ): array {
    $result = ['processed' => 0, 'sent' => 0, 'failed' => 0];
    $logs = $this->logs->findDuePending(
      $now ?? current_time('mysql'),
      $limit,
    );

    foreach ($logs as $log) {
      try {
        $sent = $this->dispatch($log->id());
      } catch (RuntimeException) {
        $sent = false;
      }

      $result['processed']++;
      $result[$sent ? 'sent' : 'failed']++;
    }

    return $result;
  }

  private function metadataId(
    NotificationData $notification,
    string $key,
  ): ?int {
    $value = $notification->metadata()[$key] ?? null;

    return is_numeric($value) && (int) $value > 0
      ? (int) $value
      : null;
  }

  private function deliver(
    NotificationData $notification,
    int $logId,
  ): bool {
    if (! is_email($notification->recipient())) {
      $this->logs->markFailed(
        $logId,
        'The notification recipient is invalid.',
        $this->nextRetryAt($logId),
      );

      return false;
    }

    try {
      $sent = $this->channel->send($notification);
    } catch (Throwable $exception) {
      $this->logs->markFailed(
        $logId,
        sanitize_text_field($exception->getMessage()),
        $this->nextRetryAt($logId),
      );

      return false;
    }

    if ($sent) {
      $this->logs->markSent($logId);
    } else {
      $this->logs->markFailed(
        $logId,
        'The notification channel reported a delivery failure.',
        $this->nextRetryAt($logId),
      );
    }

    return $sent;
  }

  private function nextRetryAt(int $logId): ?string {
    $log = $this->logs->find($logId);

    if (! $log || $log->retryCount() >= self::MAXIMUM_RETRY_ATTEMPTS) {
      return null;
    }

    $delay = self::RETRY_BASE_DELAY_SECONDS * (2 ** $log->retryCount());
    $scheduled = new \DateTimeImmutable(
      current_time('mysql'),
      wp_timezone(),
    );

    return $scheduled->modify("+{$delay} seconds")->format('Y-m-d H:i:s');
  }

  private function assertCompatibleChannel(NotificationLog $log): void {
    $channel = sanitize_key($this->channel->channel());
    $provider = $this->channel->provider() !== null
      ? sanitize_key($this->channel->provider())
      : null;

    if ($log->channel() !== $channel || $log->provider() !== $provider) {
      throw new RuntimeException(
        'The original notification channel is not available.'
      );
    }
  }

  private function notificationFromLog(
    NotificationLog $log,
  ): NotificationData {
    $payload = $log->payload();
    $content = $payload['content'] ?? null;
    $headers = $payload['headers'] ?? [];

    if (! is_string($content) || ! is_array($headers)) {
      throw new RuntimeException(
        'The stored notification payload is invalid.'
      );
    }

    return new NotificationData(
      event: $log->event(),
      recipient: $log->recipient(),
      subject: $log->subject() ?? '',
      content: $content,
      headers: array_values(array_filter(
        $headers,
        static fn(mixed $header): bool => is_string($header),
      )),
      metadata: $log->metadata() ?? [],
    );
  }
}
