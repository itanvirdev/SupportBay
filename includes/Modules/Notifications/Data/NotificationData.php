<?php

declare(strict_types=1);

namespace SupportBay\Modules\Notifications\Data;

final readonly class NotificationData {
  /**
   * @param string[] $headers
   * @param array<string, mixed> $metadata
   */
  public function __construct(
    private string $event,
    private string $recipient,
    private string $subject,
    private string $content,
    private array $headers = [],
    private array $metadata = [],
  ) {
  }

  public function event(): string {
    return $this->event;
  }

  public function recipient(): string {
    return $this->recipient;
  }

  public function subject(): string {
    return $this->subject;
  }

  public function content(): string {
    return $this->content;
  }

  /** @return string[] */
  public function headers(): array {
    return $this->headers;
  }

  /** @return array<string, mixed> */
  public function metadata(): array {
    return $this->metadata;
  }
}
