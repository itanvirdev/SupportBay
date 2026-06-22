<?php

declare(strict_types=1);

namespace SupportBay\Modules\Messages\Repositories;

use SupportBay\Modules\Messages\Database\MessageSchema;
use SupportBay\Modules\Messages\Entities\Message;
use SupportBay\Modules\Messages\Enums\MessageType;
use SupportBay\Common\Enums\AuthorType;

final class MessageRepository {
  /**
   * Create a new message
   */
  public function create(array $data): int {
    global $wpdb;

    $table = MessageSchema::tableName();

    $wpdb->insert(
      $table,
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

        'created_at'       => $data['created_at'] ?? current_time('mysql'),
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

    return (int) $wpdb->insert_id;
  }

  /**
   * Find message by ID (returns Entity)
   */
  public function find(int $id): ?Message {
    global $wpdb;

    $table = MessageSchema::tableName();

    $row = $wpdb->get_row(
      $wpdb->prepare(
        "SELECT * FROM {$table} WHERE id = %d",
        $id
      ),
      ARRAY_A
    );

    return $row ? $this->hydrate($row) : null;
  }

  /**
   * Get all messages for a ticket (returns Entity list)
   *
   * @return Message[]
   */
  public function getByTicket(int $ticketId): array {
    global $wpdb;

    $table = MessageSchema::tableName();

    $rows = $wpdb->get_results(
      $wpdb->prepare(
        "SELECT * FROM {$table} 
                 WHERE ticket_id = %d 
                 ORDER BY id ASC",
        $ticketId
      ),
      ARRAY_A
    );

    return array_map(fn($row) => $this->hydrate($row), $rows);
  }

  /**
   * Update message
   */
  public function update(int $id, array $data): bool {
    global $wpdb;

    $table = MessageSchema::tableName();

    $data['updated_at'] = current_time('mysql');

    return (bool) $wpdb->update(
      $table,
      $data,
      ['id' => $id]
    );
  }

  /**
   * Mark as read by customer
   */
  public function markCustomerRead(int $id): bool {
    global $wpdb;

    $table = MessageSchema::tableName();

    return (bool) $wpdb->update(
      $table,
      ['customer_read_at' => current_time('mysql')],
      ['id' => $id]
    );
  }

  /**
   * Mark as read by staff
   */
  public function markStaffRead(int $id): bool {
    global $wpdb;

    $table = MessageSchema::tableName();

    return (bool) $wpdb->update(
      $table,
      ['staff_read_at' => current_time('mysql')],
      ['id' => $id]
    );
  }

  /**
   * Delete message
   */
  public function delete(int $id): bool {
    global $wpdb;

    $table = MessageSchema::tableName();

    return (bool) $wpdb->delete(
      $table,
      ['id' => $id],
      ['%d']
    );
  }

  /**
   * Hydrate DB row → Message Entity
   */
  private function hydrate(array $row): Message {
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
