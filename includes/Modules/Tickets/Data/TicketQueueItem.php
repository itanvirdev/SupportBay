<?php

declare(strict_types=1);

namespace SupportBay\Modules\Tickets\Data;

final class TicketQueueItem {
  /** @param array<string, mixed> $row */
  /** @param array<int, array<string, mixed>> $tags */
  public function __construct(
    private readonly array $row,
    private readonly array $tags = [],
  ) {}

  /** @return array<string, mixed> */
  public function toArray(): array {
    return [
      'id' => (int) $this->row['id'],
      'track_id' => (string) $this->row['track_id'],
      'subject' => (string) $this->row['subject'],
      'status' => (string) $this->row['status'],
      'state' => (string) $this->row['state'],
      'priority' => (string) $this->row['priority'],
      'assigned_agent_id' => $this->row['assigned_agent_id'] !== null ? (int) $this->row['assigned_agent_id'] : null,
      'agent_name' => $this->row['agent_name'] ?: null,
      'customer_name' => $this->row['customer_name'] ?: null,
      'customer_avatar_url' => $this->row['customer_avatar_url'] ?: null,
      'department_id' => (int) $this->row['department_id'],
      'department_name' => $this->row['department_name'] ?: null,
      'category_id' => $this->row['category_id'] !== null
        ? (int) $this->row['category_id']
        : null,
      'category_name' => $this->row['category_name'] ?: null,
      'tags' => $this->tags,
      'reply_count' => (int) $this->row['reply_count'],
      'needs_reply' => (bool) $this->row['needs_reply'],
      'sla_state' => (string) ($this->row['sla_state'] ?? 'disabled'),
      'sla_target_minutes' => (int) ($this->row['sla_target_minutes'] ?? 0),
      'sla_due_at' => $this->row['sla_due_at'] ?? null,
      'sla_remaining_minutes' => isset($this->row['sla_remaining_minutes']) ? (int) $this->row['sla_remaining_minutes'] : null,
      'last_reply_at' => $this->row['last_reply_at'] ?: null,
      'created_at' => (string) $this->row['created_at'],
      'updated_at' => $this->row['updated_at'] ?: null,
    ];
  }
}
