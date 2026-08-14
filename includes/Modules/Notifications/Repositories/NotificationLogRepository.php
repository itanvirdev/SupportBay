<?php

declare(strict_types=1);

namespace SupportBay\Modules\Notifications\Repositories;

use SupportBay\Core\Database\Repository;
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

  public function markSent(int $id): bool {
    return $this->updateById($id, [
      'status' => NotificationStatus::SENT->value,
      'sent_at' => $this->now(),
      'error_message' => null,
      'updated_at' => $this->now(),
    ], ['%s', '%s', '%s', '%s']);
  }

  public function markFailed(int $id, string $error): bool {
    return $this->updateById($id, [
      'status' => NotificationStatus::FAILED->value,
      'error_message' => $error,
      'updated_at' => $this->now(),
    ], ['%s', '%s', '%s']);
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
           updated_at = %s
       WHERE id = %d
         AND status IN (%s, %s)
         AND retry_count < %d",
      NotificationStatus::PROCESSING->value,
      $this->now(),
      $id,
      NotificationStatus::PENDING->value,
      NotificationStatus::FAILED->value,
      max(1, $maximumAttempts),
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
