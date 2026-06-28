<?php

declare(strict_types=1);

namespace SupportBay\Modules\Tickets\Repositories;

use SupportBay\Core\Database\Repository;
use SupportBay\Common\Enums\AuthorType;
use SupportBay\Common\Enums\SourceType;
use SupportBay\Modules\Tickets\Enums\TicketPriority;
use SupportBay\Modules\Tickets\Enums\TicketState;
use SupportBay\Modules\Tickets\Enums\TicketStatus;
use SupportBay\Modules\Tickets\Database\TicketSchema;
use SupportBay\Modules\Tickets\Entities\Ticket;

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
    return $this->findById($id);
  }

  /**
   * Find ticket by track_id
   */
  public function findByTrackId(string $trackId): ?Ticket {
    return $this->first([
      'track_id' => $trackId,
    ]);
  }

  /**
   * Get tickets by customer
   */
  public function getByCustomer(int $customerId): array {
    return $this->findWhere([
      'customer_id' => $customerId,
    ], 'id', 'DESC');
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

  /**
   * Hydrate DB row → Ticket Entity
   */
  protected function hydrate(array $row): object {
    return new Ticket(
      id: (int) $row['id'],
      trackId: (string) $row['track_id'],

      customerId: isset($row['customer_id'])
        ? (int) $row['customer_id']
        : null,

      createdById: isset($row['created_by_id'])
        ? (int) $row['created_by_id']
        : null,

      createdByType: AuthorType::from($row['created_by_type']),

      purchaseVerificationId: isset($row['purchase_verification_id'])
        ? (int) $row['purchase_verification_id']
        : null,

      departmentId: (int) $row['department_id'],

      assignedAgentId: isset($row['assigned_agent_id'])
        ? (int) $row['assigned_agent_id']
        : null,

      subject: (string) $row['subject'],

      status: TicketStatus::from($row['status']),
      state: TicketState::from($row['state']),
      priority: TicketPriority::from($row['priority']),
      source: SourceType::from($row['source']),

      lastMessageId: isset($row['last_message_id'])
        ? (int) $row['last_message_id']
        : null,

      lastReplyAt: $row['last_reply_at'] ?? null,
      firstResponseAt: $row['first_response_at'] ?? null,
      resolvedAt: $row['resolved_at'] ?? null,
      closedAt: $row['closed_at'] ?? null,
      reopenedAt: $row['reopened_at'] ?? null,

      isPublic: (bool) $row['is_public'],
      publicToken: $row['public_token'] ?? null,

      metadata: $row['metadata'] ?? null,

      createdAt: $row['created_at'],
      updatedAt: $row['updated_at'] ?? null,
    );
  }
}
