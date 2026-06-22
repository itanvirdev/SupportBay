<?php

declare(strict_types=1);

namespace SupportBay\Modules\Tickets\Repositories;

use SupportBay\Modules\Tickets\Database\TicketSchema;

final class TicketRepository {
  /**
   * Create a new ticket
   */
  public function create(array $data): int {
    global $wpdb;

    $table = TicketSchema::tableName();

    $wpdb->insert(
      $table,
      [
        'track_id'          => $data['track_id'],
        'customer_id'       => $data['customer_id'] ?? null,
        'created_by_id'       => $data['created_by_id'] ?? null,
        'created_by_type' => $data['created_by_type'] ?? 'customer',
        'purchase_verification_id' => $data['purchase_verification_id'] ?? null,
        'department_id' => $data['department_id'],
        'assigned_agent_id' => $data['assigned_agent_id'] ?? null,
        'subject'           => $data['subject'] ?? '',
        'status'            => $data['status'] ?? 'open',
        'state'             => $data['state'] ?? 'active',
        'priority'          => $data['priority'] ?? 'normal',
        'source'          => $data['source'] ?? 'web',
        'last_message_id'      => $data['last_message_id'] ?? null,
        'last_reply_at'      => $data['last_reply_at'] ?? null,
        'first_response_at'      => $data['first_response_at'] ?? null,
        'resolved_at'      => $data['resolved_at'] ?? null,
        'closed_at'      => $data['closed_at'] ?? null,
        'reopened_at'      => $data['reopened_at'] ?? null,
        'is_public'         => $data['is_public'] ?? 0,
        'public_token' => $data['public_token'] ?? null,
        'metadata'         => $data['metadata'] ?? null,
        'created_at'        =>  $data['created_at'] ?? current_time('mysql'),
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
      ]
    );

    return (int) $wpdb->insert_id;
  }

  /**
   * Find ticket by ID
   */
  public function find(int $id): ?array {
    global $wpdb;

    $table = TicketSchema::tableName();

    $result = $wpdb->get_row(
      $wpdb->prepare(
        "SELECT * FROM {$table} WHERE id = %d",
        $id
      ),
      ARRAY_A
    );

    return $result ?: null;
  }

  /**
   * Find ticket by track_id
   */
  public function findByTrackId(string $trackId): ?array {
    global $wpdb;

    $table = TicketSchema::tableName();

    $result = $wpdb->get_row(
      $wpdb->prepare(
        "SELECT * FROM {$table} WHERE track_id = %s",
        $trackId
      ),
      ARRAY_A
    );

    return $result ?: null;
  }

  /**
   * Get tickets by customer
   */
  public function getByCustomer(int $customerId): array {
    global $wpdb;

    $table = TicketSchema::tableName();

    return $wpdb->get_results(
      $wpdb->prepare(
        "SELECT * FROM {$table} WHERE customer_id = %d ORDER BY id DESC",
        $customerId
      ),
      ARRAY_A
    );
  }

  /**
   * Update ticket
   */
  public function update(int $id, array $data): bool {
    global $wpdb;

    $table = TicketSchema::tableName();

    $data['updated_at'] = current_time('mysql');

    return (bool) $wpdb->update(
      $table,
      $data,
      ['id' => $id]
    );
  }

  /**
   * Delete ticket (soft-delete can be added later)
   */
  public function delete(int $id): bool {
    global $wpdb;

    $table = TicketSchema::tableName();

    return (bool) $wpdb->delete(
      $table,
      ['id' => $id],
      ['%d']
    );
  }
}
