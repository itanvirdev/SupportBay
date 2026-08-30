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
   * Table
   */
  protected function table(): string {
    return MessageSchema::tableName();
  }

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
   * Find message by ID (returns Entity)
   */
  public function find(int $id): ?Message {
    return $this->findById($id);
  }

  /**
   * Get all messages for a ticket (returns Entity list)
   *
   * @return Message[]
   */
  public function getByTicket(int $ticketId): array {
    return $this->findWhere([
      'ticket_id' => $ticketId,
    ], 'id', 'ASC');
  }

  /**
   * Return the author type of the latest public reply for each ticket.
   *
   * @param int[] $ticketIds
   * @return array<int, string>
   */
  public function latestReplyAuthorTypes(array $ticketIds): array {
    return array_map(
      static fn(array $summary): string => $summary['author_type'],
      $this->latestReplySummaries($ticketIds),
    );
  }

  /**
   * Return latest public reply data and total reply count by ticket.
   *
   * @param int[] $ticketIds
   * @return array<int, array{author_type: string, content: string, reply_count: int}>
   */
  public function latestReplySummaries(array $ticketIds): array {
    $ticketIds = array_values(array_unique(array_filter(array_map('absint', $ticketIds))));
    if ($ticketIds === []) {
      return [];
    }

    $placeholders = implode(',', array_fill(0, count($ticketIds), '%d'));
    $table = $this->table();
    $rows = $this->db->get_results($this->db->prepare(
      "SELECT messages.ticket_id, messages.author_type, messages.content, latest.reply_count
       FROM {$table} messages
       INNER JOIN (
         SELECT ticket_id, MAX(id) latest_reply_id, COUNT(*) reply_count
         FROM {$table}
         WHERE ticket_id IN ({$placeholders}) AND type = %s
         GROUP BY ticket_id
       ) latest ON latest.latest_reply_id = messages.id",
      ...[...$ticketIds, MessageType::REPLY->value],
    ), ARRAY_A);

    $summaries = [];
    foreach ($rows as $row) {
      $summaries[(int) $row['ticket_id']] = [
        'author_type' => (string) $row['author_type'],
        'content' => (string) $row['content'],
        'reply_count' => (int) $row['reply_count'],
      ];
    }

    return $summaries;
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
  public function deleteByTicket(int $ticketId):int{return(int)$this->db->delete($this->table(),['ticket_id'=>$ticketId],['%d']);}

  public function moveToTicket(int $sourceTicketId, int $targetTicketId): int {
    $result = $this->db->update(
      $this->table(),
      ['ticket_id' => $targetTicketId, 'updated_at' => $this->now()],
      ['ticket_id' => $sourceTicketId],
      ['%d', '%s'],
      ['%d'],
    );

    if ($result === false) {
      throw new \RuntimeException('Ticket messages could not be moved.');
    }

    return $result;
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
