<?php

declare(strict_types=1);

namespace SupportBay\Modules\Tickets\Repositories;

use SupportBay\Core\Database\Repository;
use SupportBay\Modules\Tickets\Database\TicketSchema;

final class TicketRepository extends Repository {

  /**
   * Table
   */
  protected function table(): string {
    return TicketSchema::tableName();
  }


  /**
   * Create a new ticket
   */
  public function create(array $data): int {
    return $this->insert(
      [
        'track_id'                 => $data['track_id'],
        'customer_id'              => $data['customer_id'] ?? null,
        'created_by_id'            => $data['created_by_id'] ?? null,
        'created_by_type'          => $data['created_by_type'],
        'purchase_verification_id' => $data['purchase_verification_id'] ?? null,
        'department_id'            => $data['department_id'],
        'assigned_agent_id'        => $data['assigned_agent_id'] ?? null,
        'subject'                  => $data['subject'],
        'status'                   => $data['status'],
        'state'                    => $data['state'],
        'priority'                 => $data['priority'],
        'source'                   => $data['source'],
        'last_message_id'          => $data['last_message_id'] ?? null,
        'last_reply_at'            => $data['last_reply_at'] ?? null,
        'first_response_at'        => $data['first_response_at'] ?? null,
        'resolved_at'              => $data['resolved_at'] ?? null,
        'closed_at'                => $data['closed_at'] ?? null,
        'reopened_at'              => $data['reopened_at'] ?? null,
        'is_public'                => $data['is_public'] ?? 0,
        'public_token'             => $data['public_token'] ?? null,
        'metadata'                 => $data['metadata'] ?? null,
        'created_at'               => $data['created_at'] ?? $this->now(),
        'updated_at'               => $data['updated_at'] ?? $this->now(),
      ],
      [
        '%s', // track_id
        '%d', // customer_id
        '%d', // created_by_id
        '%s', // created_by_type
        '%d', // purchase_verification_id
        '%d', // department_id
        '%d', // assigned_agent_id
        '%s', // subject
        '%s', // status
        '%s', // state
        '%s', // priority
        '%s', // source
        '%d', // last_message_id
        '%s', // last_reply_at
        '%s', // first_response_at
        '%s', // resolved_at
        '%s', // closed_at
        '%s', // reopened_at
        '%d', // is_public
        '%s', // public_token
        '%s', // metadata
        '%s', // created_at
        '%s', // updated_at
      ]
    );
  }


  /**
   * Find ticket by ID
   */
  public function find(int $id): ?Ticket {
    $result = $this->db->get_row(
      $this->db->prepare(
        "SELECT * FROM {$this->table()} WHERE id = %d",
        $id
      ),
      ARRAY_A
    );

    return $result ?: null;
  }

  /**
   * Find ticket by track_id
   */
  public function findByTrackId(string $trackId): ?Ticket {
    $result = $this->db->get_row(
      $this->db->prepare(
        "SELECT * FROM {$this->table()} WHERE track_id = %s",
        $trackId
      ),
      ARRAY_A
    );

    return $result ?: null;
  }

  /**
   * Get tickets by customer
   */
  public function getByCustomer(int $customerId): ?Ticket {
    return $this->db->get_results(
      $this->db->prepare(
        "SELECT * FROM {$this->table()}
         WHERE customer_id = %d
         ORDER BY id DESC",
        $customerId
      ),
      ARRAY_A
    );
  }

  /**
   * Update ticket
   */
  public function update(int $id, array $data): bool {
    $data['updated_at'] = $this->now();

    return $this->updateById($id, $data);
  }

  /**
   * Delete ticket (soft-delete can be added later)
   */
  public function delete(int $id): bool {
    return $this->deleteById($id);
  }
}
