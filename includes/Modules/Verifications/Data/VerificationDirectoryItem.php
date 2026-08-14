<?php

declare(strict_types=1);

namespace SupportBay\Modules\Verifications\Data;

final class VerificationDirectoryItem {
  /** @param array<string, mixed> $row */
  public function __construct(private readonly array $row) {
  }

  /** @return array<string, mixed> */
  public function toArray(): array {
    $supportExpiry = $this->row['support_expires_at'] ?: null;

    return [
      'id' => (int) $this->row['id'],
      'provider' => (string) $this->row['provider'],
      'reference' => self::mask((string) $this->row['provider_reference']),
      'customer_id' => $this->row['customer_id'] !== null ? (int) $this->row['customer_id'] : null,
      'customer_name' => $this->row['customer_name'] ?: null,
      'product_id' => $this->row['product_id'] ?: null,
      'product_name' => $this->row['product_name'] ?: null,
      'license_type' => $this->row['license_type'] ?: null,
      'support_expires_at' => $supportExpiry,
      'support_status' => $supportExpiry === null ? 'unknown' : (strtotime($supportExpiry) <= current_time('timestamp') ? 'expired' : 'active'),
      'verification_status' => (string) $this->row['verification_status'],
      'ticket_count' => (int) $this->row['ticket_count'],
      'last_checked_at' => $this->row['last_checked_at'] ?: null,
      'verified_at' => $this->row['verified_at'] ?: null,
      'updated_at' => (string) $this->row['updated_at'],
    ];
  }

  public static function mask(string $reference): string {
    $length = strlen($reference);

    if ($length <= 4) {
      return str_repeat('•', $length);
    }

    return str_repeat('•', min(12, $length - 4)) . substr($reference, -4);
  }
}
