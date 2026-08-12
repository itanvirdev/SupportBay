<?php

declare(strict_types=1);

namespace SupportBay\Core\Integrations\Data;

final class ProviderConnectionTestData {
  public function __construct(
    private readonly bool $successful,
    private readonly string $message,
  ) {
  }

  /** @return array{successful: bool, message: string} */
  public function toArray(): array {
    return [
      'successful' => $this->successful,
      'message' => $this->message,
    ];
  }

  public function isSuccessful(): bool { return $this->successful; }
  public function message(): string { return $this->message; }
}
