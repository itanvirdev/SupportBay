<?php

declare(strict_types=1);

namespace SupportBay\Modules\Notifications\Repositories;

use SupportBay\Core\Database\Repository;
use SupportBay\Modules\Notifications\Data\NotificationLogQuery;
use SupportBay\Modules\Notifications\Database\NotificationLogSchema;
use SupportBay\Modules\Notifications\Entities\NotificationLog;
use SupportBay\Modules\Notifications\Enums\NotificationStatus;

final class NotificationLogRepository extends Repository {
  protected function table(): string {
    return NotificationLogSchema::tableName();
  }

  /** @param array<string, mixed> $data */
  public function create(array $data): int {
    return $this->insert([
      'ticket_id' => $data['ticket_id'] ?? null,
      'user_id' => $data['user_id'] ?? null,
      'channel' => $data['channel'],
      'event' => $data['event'],
      'recipient' => $data['recipient'],
      'subject' => $data['subject'] ?? null,
      'payload' => $data['payload'] ?? null,
      'status' => $data['status'],
      'provider' => $data['provider'] ?? null,
      'provider_message_id' => $data['provider_message_id'] ?? null,
      'error_message' => $data['error_message'] ?? null,
      'retry_count' => $data['retry_count'] ?? 0,
      'scheduled_at' => $data['scheduled_at'] ?? null,
      'sent_at' => $data['sent_at'] ?? null,
      'delivered_at' => $data['delivered_at'] ?? null,
      'metadata' => $data['metadata'] ?? null,
      'created_at' => $data['created_at'] ?? $this->now(),
      'updated_at' => $data['updated_at'] ?? $this->now(),
    ], [
      '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
      '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s',
    ]);
  }

  public function find(int $id): ?NotificationLog {
    $log = $this->findById($id);

    return $log instanceof NotificationLog ? $log : null;
  }

  /** @return NotificationLog[] */
  public function findByTicket(int $ticketId): array {
    return $this->findWhere(
      ['ticket_id' => $ticketId],
      'id',
      'ASC',
    );
  }

  /** @return NotificationLog[] */
  public function findDueForRetry(
    string $now,
    int $maximumAttempts,
    int $limit = 20,
  ): array {
    $rows = $this->db->get_results($this->db->prepare(
      "SELECT * FROM {$this->table()}
       WHERE status = %s
         AND scheduled_at IS NOT NULL
         AND scheduled_at <= %s
         AND retry_count < %d
       ORDER BY scheduled_at ASC, id ASC
       LIMIT %d",
      NotificationStatus::FAILED->value,
      $now,
      max(1, $maximumAttempts),
      max(1, min(100, $limit)),
    ), ARRAY_A);

    return array_map(
      fn(array $row): NotificationLog => $this->hydrate($row),
      $rows,
    );
  }

  /** @return NotificationLog[] */
  public function findDuePending(string $now, int $limit = 20): array {
    $rows = $this->db->get_results($this->db->prepare(
      "SELECT * FROM {$this->table()}
       WHERE status = %s
         AND scheduled_at IS NOT NULL
         AND scheduled_at <= %s
       ORDER BY scheduled_at ASC, id ASC
       LIMIT %d",
      NotificationStatus::PENDING->value,
      $now,
      max(1, min(100, $limit)),
    ), ARRAY_A);

    return array_map(
      fn(array $row): NotificationLog => $this->hydrate($row),
      $rows,
    );
  }

  /** @return array{items: NotificationLog[], total: int} */
  public function search(NotificationLogQuery $query): array {
    $clauses = [];
    $values = [];

    foreach (['channel', 'event', 'status'] as $field) {
      if ($query->{$field} !== null && $query->{$field} !== '') {
        $clauses[] = "{$field} = %s";
        $values[] = $query->{$field};
      }
    }

    if ($query->search !== null && $query->search !== '') {
      $like = '%' . $this->db->esc_like($query->search) . '%';
      $clauses[] = '(recipient LIKE %s OR subject LIKE %s OR error_message LIKE %s)';
      array_push($values, $like, $like, $like);
    }

    $where = $clauses !== []
      ? 'WHERE ' . implode(' AND ', $clauses)
      : '';
    $countSql = "SELECT COUNT(*) FROM {$this->table()} {$where}";
    $total = (int) ($values !== []
      ? $this->db->get_var($this->db->prepare($countSql, ...$values))
      : $this->db->get_var($countSql));
    $orderBy = match ($query->orderBy) {
      'recipient' => 'recipient',
      'event' => 'event',
      'status' => 'status',
      'sent_at' => 'sent_at',
      default => 'created_at',
    };
    $direction = strtoupper($query->direction) === 'ASC' ? 'ASC' : 'DESC';
    $sql = "SELECT * FROM {$this->table()} {$where} ORDER BY {$orderBy} {$direction}, id DESC LIMIT %d OFFSET %d";
    $rows = $this->db->get_results($this->db->prepare(
      $sql,
      ...[
        ...$values,
        max(1, min(100, $query->perPage)),
        max(0, ($query->page - 1) * $query->perPage),
      ],
    ), ARRAY_A);

    return [
      'items' => array_map(
        fn(array $row): NotificationLog => $this->hydrate($row),
        $rows,
      ),
      'total' => $total,
    ];
  }

  public function markSent(int $id): bool {
    return $this->updateById($id, [
      'status' => NotificationStatus::SENT->value,
      'sent_at' => $this->now(),
      'error_message' => null,
      'scheduled_at' => null,
      'updated_at' => $this->now(),
    ], ['%s', '%s', '%s', '%s', '%s']);
  }

  public function markFailed(
    int $id,
    string $error,
    ?string $scheduledAt = null,
  ): bool {
    return $this->updateById($id, [
      'status' => NotificationStatus::FAILED->value,
      'error_message' => $error,
      'scheduled_at' => $scheduledAt,
      'updated_at' => $this->now(),
    ], ['%s', '%s', '%s', '%s']);
  }

  public function schedulePending(int $id, string $scheduledAt): bool {
    $result = $this->db->query($this->db->prepare(
      "UPDATE {$this->table()}
       SET scheduled_at = %s,
           updated_at = %s
       WHERE id = %d
         AND status = %s",
      $scheduledAt,
      $this->now(),
      $id,
      NotificationStatus::PENDING->value,
    ));

    return $result === 1;
  }

  /**
   * Atomically claim a retry attempt.
   */
  public function beginRetry(
    int $id,
    int $maximumAttempts,
  ): bool {
    $result = $this->db->query($this->db->prepare(
      "UPDATE {$this->table()}
       SET status = %s,
           retry_count = retry_count + 1,
           error_message = NULL,
           scheduled_at = NULL,
           updated_at = %s
       WHERE id = %d
         AND status = %s
         AND retry_count < %d",
      NotificationStatus::PROCESSING->value,
      $this->now(),
      $id,
      NotificationStatus::FAILED->value,
      max(1, $maximumAttempts),
    ));

    return $result === 1;
  }

  public function beginDispatch(int $id): bool {
    $result = $this->db->query($this->db->prepare(
      "UPDATE {$this->table()}
       SET status = %s,
           error_message = NULL,
           scheduled_at = NULL,
           updated_at = %s
       WHERE id = %d
         AND status = %s",
      NotificationStatus::PROCESSING->value,
      $this->now(),
      $id,
      NotificationStatus::PENDING->value,
    ));

    return $result === 1;
  }

  /** Test cleanup only; production logs remain audit records. */
  public function deleteByTicket(int $ticketId): int {
    return (int) $this->db->delete(
      $this->table(),
      ['ticket_id' => $ticketId],
      ['%d'],
    );
  }

  /** Test cleanup only; production logs remain audit records. */
  public function deleteByRecipient(string $recipient): int {
    return (int) $this->db->delete(
      $this->table(),
      ['recipient' => $recipient],
      ['%s'],
    );
  }

  protected function hydrate(array $row): object {
    return new NotificationLog(
      id: (int) $row['id'],
      ticketId: isset($row['ticket_id']) ? (int) $row['ticket_id'] : null,
      userId: isset($row['user_id']) ? (int) $row['user_id'] : null,
      channel: (string) $row['channel'],
      event: (string) $row['event'],
      recipient: (string) $row['recipient'],
      subject: $row['subject'] ?? null,
      payload: $this->json($row['payload'] ?? null),
      status: NotificationStatus::from((string) $row['status']),
      provider: $row['provider'] ?? null,
      providerMessageId: $row['provider_message_id'] ?? null,
      errorMessage: $row['error_message'] ?? null,
      retryCount: (int) $row['retry_count'],
      scheduledAt: $row['scheduled_at'] ?? null,
      sentAt: $row['sent_at'] ?? null,
      deliveredAt: $row['delivered_at'] ?? null,
      metadata: $this->json($row['metadata'] ?? null),
      createdAt: (string) $row['created_at'],
      updatedAt: (string) $row['updated_at'],
    );
  }

  /** @return array<string, mixed>|null */
  private function json(mixed $value): ?array {
    if (! is_string($value) || $value === '') {
      return null;
    }

    $decoded = json_decode($value, true);

    return is_array($decoded) ? $decoded : null;
  }
}
