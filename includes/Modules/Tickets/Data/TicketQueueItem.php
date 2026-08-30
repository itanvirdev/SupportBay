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
      'customer_avatar_url' => ! empty($this->row['customer_avatar_url'])
        ? (string) $this->row['customer_avatar_url']
        : (! empty($this->row['customer_user_id'])
          ? get_avatar_url((int) $this->row['customer_user_id'], [
            'size' => 64,
          ])
          : null),
      'department_id' => (int) $this->row['department_id'],
      'department_name' => $this->row['department_name'] ?: null,
      'category_id' => $this->row['category_id'] !== null
        ? (int) $this->row['category_id']
        : null,
      'category_name' => $this->row['category_name'] ?: null,
      'tags' => $this->tags,
      'reply_count' => (int) $this->row['reply_count'],
      'latest_reply_excerpt' => isset($this->row['latest_reply_excerpt'])
        ? (string) $this->row['latest_reply_excerpt']
        : wp_trim_words(
          wp_strip_all_tags((string) ($this->row['latest_reply_content'] ?? '')),
          22,
          '…',
        ),
      'needs_reply' => (bool) $this->row['needs_reply'],
      'last_reply_at' => $this->row['last_reply_at'] ?: null,
      'created_at' => (string) $this->row['created_at'],
      'updated_at' => $this->row['updated_at'] ?: null,
    ];
  }
}
