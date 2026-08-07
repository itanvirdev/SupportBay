<?php

declare(strict_types=1);

namespace SupportBay\Modules\Webhooks\Data;

final readonly class WebhookData {
  /** @param array<string, mixed> $payload */
  public function __construct(
    private string $event,
    private array $payload,
    private string $occurredAt,
  ) {
  }

  public function event(): string {
    return $this->event;
  }

  /** @return array<string, mixed> */
  public function payload(): array {
    return $this->payload;
  }

  public function occurredAt(): string {
    return $this->occurredAt;
  }

  /** @return array<string, mixed> */
  public function toArray(): array {
    return [
      'event'       => $this->event,
      'occurred_at' => $this->occurredAt,
      'payload'     => $this->payload,
    ];
  }
}
