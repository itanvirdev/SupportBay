<?php

declare(strict_types=1);

namespace SupportBay\Modules\Messages\Repositories;

use SupportBay\Common\Enums\AuthorType;
use SupportBay\Core\Database\Repository;
use SupportBay\Modules\Messages\Database\MessageSchema;
use SupportBay\Modules\Messages\Entities\Message;
use SupportBay\Modules\Messages\Enums\MessageType;

final class MessageRepository extends Repository {
  /**
   * Create a new message
   */
  public function create(array $data): int {
    return $this->insert(
      [
        'ticket_id'        => $data['ticket_id'],

        'author_id'        => $data['author_id'] ?? null,
        'author_type'      => $data['author_type'],

        'type'             => $data['type'],

        'content'          => $data['content'],

        'edited_by_id'     => $data['edited_by_id'] ?? null,
        'edited_at'        => $data['edited_at'] ?? null,

        'customer_read_at' => $data['customer_read_at'] ?? null,
        'staff_read_at'    => $data['staff_read_at'] ?? null,

        'metadata'         => $data['metadata'] ?? null,

        'created_at'       => $data['created_at'] ?? $this->now(),
      ],
      [
        '%d', // ticket_id

        '%d', // author_id
        '%s', // author_type

        '%s', // type

        '%s', // content

        '%d', // edited_by_id
        '%s', // edited_at

        '%s', // customer_read_at
        '%s', // staff_read_at

        '%s', // metadata

        '%s', // created_at
      ]
    );
  }

  /**
   * Table
   */
  protected function table(): string {
    return MessageSchema::tableName();
  }

  /**
   * Find message by ID (returns Entity)
   */
  public function find(int $id): ?Message {
    $result = $this->db->get_row(
      $this->db->prepare(
        "SELECT * FROM {$this->table()} WHERE id = %d",
        $id
      ),
      ARRAY_A
    );

    return $result
      ? $this->hydrate($result)
      : null;
  }

  /**
   * Get all messages for a ticket (returns Entity list)
   *
   * @return Message[]
   */
  public function getByTicket(int $ticketId): array {
    $result = $this->db->get_results(
      $this->db->prepare(
        "SELECT *
         FROM {$this->table()}
         WHERE ticket_id = %d
         ORDER BY id ASC",
        $ticketId
      ),
      ARRAY_A
    );

    return array_map(
      fn(array $row) => $this->hydrate($row),
      $result
    );
  }

  /**
   * Update message
   */
  public function update(int $id, array $data): bool {
    $data['updated_at'] = $this->now();

    return $this->updateById($id, $data);
  }

  /**
   * Mark as read by customer
   */
  public function markCustomerRead(int $id): bool {
    return $this->updateById($id, [
      'customer_read_at' => $this->now(),
    ]);
  }

  /**
   * Mark as read by staff
   */
  public function markStaffRead(int $id): bool {
    return $this->updateById($id, [
      'staff_read_at' => $this->now(),
    ]);
  }

  /**
   * Delete message
   */
  public function delete(int $id): bool {
    return $this->deleteById($id);
  }

  /**
   * Hydrate DB row → Message Entity
   */
  protected function hydrate(array $row): Message {
    return new Message(
      id: (int) $row['id'],
      ticketId: (int) $row['ticket_id'],
      authorId: isset($row['author_id']) ? (int) $row['author_id'] : null,
      authorType: AuthorType::from($row['author_type']),
      type: MessageType::from($row['type']),
      content: (string) $row['content'],
      editedById: isset($row['edited_by_id']) ? (int) $row['edited_by_id'] : null,
      editedAt: $row['edited_at'] ?? null,
      customerReadAt: $row['customer_read_at'] ?? null,
      staffReadAt: $row['staff_read_at'] ?? null,
      metadata: $row['metadata'] ?? null,
      createdAt: $row['created_at'],
      updatedAt: $row['updated_at'] ?? null,
    );
  }
}
