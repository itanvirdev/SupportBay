<?php

declare(strict_types=1);

namespace SupportBay\Modules\Admin\Data;

final class CustomerDirectoryItem {
  /** @param array<string, mixed> $row */
  public function __construct(private readonly array $row) {}

  /** @return array<string, mixed> */
  public function toArray(): array {
    return [
      'id' => (int) $this->row['id'],
      'display_name' => (string) $this->row['display_name'],
      'email' => (string) $this->row['email'],
      'avatar_url' => $this->row['avatar_url'] ?: null,
      'state' => (string) $this->row['state'],
      'source' => (string) $this->row['source'],
      'company' => $this->row['company'] ?: null,
      'phone' => $this->row['phone'] ?: null,
      'country' => $this->row['country'] ?: null,
      'last_login_at' => $this->row['last_login_at'] ?: null,
      'ticket_count' => (int) $this->row['ticket_count'],
      'open_ticket_count' => (int) $this->row['open_ticket_count'],
      'purchase_count' => (int) $this->row['purchase_count'],
      'verified_purchase_count' => (int) $this->row['verified_purchase_count'],
      'last_activity_at' => $this->row['last_activity_at'] ?: null,
      'created_at' => (string) $this->row['created_at'],
    ];
  }
}
