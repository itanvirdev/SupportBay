<?php

declare(strict_types=1);

namespace SupportBay\Modules\Notifications\Services;

use Throwable;
use SupportBay\Modules\Notifications\Contracts\NotificationChannel;
use SupportBay\Modules\Notifications\Data\NotificationData;
use SupportBay\Modules\Notifications\Entities\NotificationLog;
use SupportBay\Modules\Notifications\Enums\NotificationStatus;
use SupportBay\Modules\Notifications\Repositories\NotificationLogRepository;

final class NotificationService {
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

    if (! is_email($notification->recipient())) {
      $this->logs->markFailed($logId, 'The notification recipient is invalid.');

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
}
