<?php

declare(strict_types=1);

namespace SupportBay\Modules\Notifications\Entities;

use SupportBay\Core\Entities\Entity;
use SupportBay\Modules\Notifications\Enums\NotificationStatus;

final class NotificationLog extends Entity {
  /** @param array<string, mixed>|null $payload @param array<string, mixed>|null $metadata */
  public function __construct(
    private int $id,
    private ?int $ticketId,
    private ?int $userId,
    private string $channel,
    private string $event,
    private string $recipient,
    private ?string $subject,
    private ?array $payload,
    private NotificationStatus $status,
    private ?string $provider,
    private ?string $providerMessageId,
    private ?string $errorMessage,
    private int $retryCount,
    private ?string $scheduledAt,
    private ?string $sentAt,
    private ?string $deliveredAt,
    private ?array $metadata,
    private string $createdAt,
    private string $updatedAt,
  ) {
  }

  public function toArray(): array {
    return [
      'id' => $this->id,
      'ticket_id' => $this->ticketId,
      'user_id' => $this->userId,
      'channel' => $this->channel,
      'event' => $this->event,
      'recipient' => $this->recipient,
      'subject' => $this->subject,
      'payload' => $this->payload,
      'status' => $this->status->value,
      'provider' => $this->provider,
      'provider_message_id' => $this->providerMessageId,
      'error_message' => $this->errorMessage,
      'retry_count' => $this->retryCount,
      'scheduled_at' => $this->scheduledAt,
      'sent_at' => $this->sentAt,
      'delivered_at' => $this->deliveredAt,
      'metadata' => $this->metadata,
      'created_at' => $this->createdAt,
      'updated_at' => $this->updatedAt,
    ];
  }

  public function id(): int { return $this->id; }
  public function ticketId(): ?int { return $this->ticketId; }
  public function userId(): ?int { return $this->userId; }
  public function channel(): string { return $this->channel; }
  public function event(): string { return $this->event; }
  public function recipient(): string { return $this->recipient; }
  public function subject(): ?string { return $this->subject; }
  /** @return array<string, mixed>|null */
  public function payload(): ?array { return $this->payload; }
  public function status(): NotificationStatus { return $this->status; }
  public function provider(): ?string { return $this->provider; }
  public function providerMessageId(): ?string { return $this->providerMessageId; }
  public function errorMessage(): ?string { return $this->errorMessage; }
  public function retryCount(): int { return $this->retryCount; }
  public function scheduledAt(): ?string { return $this->scheduledAt; }
  public function sentAt(): ?string { return $this->sentAt; }
  public function deliveredAt(): ?string { return $this->deliveredAt; }
  /** @return array<string, mixed>|null */
  public function metadata(): ?array { return $this->metadata; }
  public function createdAt(): string { return $this->createdAt; }
  public function updatedAt(): string { return $this->updatedAt; }

  public function wasSent(): bool { return $this->status->isSuccessful(); }
  public function failed(): bool { return $this->status === NotificationStatus::FAILED; }
  public function canRetry(): bool { return $this->status->canRetry(); }
}
