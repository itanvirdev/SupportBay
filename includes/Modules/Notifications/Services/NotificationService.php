<?php

declare(strict_types=1);

namespace SupportBay\Modules\Notifications\Services;

use RuntimeException;
use Throwable;
use SupportBay\Modules\Notifications\Contracts\NotificationChannel;
use SupportBay\Modules\Notifications\Data\NotificationData;
use SupportBay\Modules\Notifications\Entities\NotificationLog;
use SupportBay\Modules\Notifications\Enums\NotificationStatus;
use SupportBay\Modules\Notifications\Repositories\NotificationLogRepository;

final class NotificationService {
  private const MAXIMUM_RETRY_ATTEMPTS = 3;

  public function __construct(
    private readonly NotificationChannel $channel,
    private readonly NotificationLogRepository $logs,
  ) {
  }

  public function send(NotificationData $notification): bool {
    $logId = $this->logs->create([
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
      'provider' => $this->channel->provider() !== null
        ? sanitize_key($this->channel->provider())
        : null,
      'metadata' => wp_json_encode($notification->metadata()),
    ]);

    return $this->deliver($notification, $logId);
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

  /** @return NotificationLog[] */
  public function logsForTicket(int $ticketId): array {
    return $this->logs->findByTicket($ticketId);
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
      );

      return false;
    }

    try {
      $sent = $this->channel->send($notification);
    } catch (Throwable $exception) {
      $this->logs->markFailed(
        $logId,
        sanitize_text_field($exception->getMessage()),
      );

      return false;
    }

    if ($sent) {
      $this->logs->markSent($logId);
    } else {
      $this->logs->markFailed(
        $logId,
        'The notification channel reported a delivery failure.',
      );
    }

    return $sent;
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
