<?php

declare(strict_types=1);

namespace SupportBay\Modules\Activities\Repositories;

use SupportBay\Common\Enums\AuthorType;
use SupportBay\Core\Database\Repository;
use SupportBay\Modules\Activities\Database\ActivitySchema;
use SupportBay\Modules\Activities\Entities\Activity;
use SupportBay\Modules\Activities\Enums\ActivityType;

final class ActivityRepository extends Repository {
  /**
   * Table
   */
  protected function table(): string {
    return ActivitySchema::tableName();
  }

  /**
   * Create activity
   */
  public function create(array $data): int {
    return $this->insert(
      [
        'ticket_id'   => $data['ticket_id'],

        'actor_id'    => $data['actor_id'] ?? null,
        'actor_type'  => $data['actor_type'],

        'event_type'  => $data['event_type'],

        'description' => $data['description'] ?? null,

        'payload'     => $data['payload'] ?? null,

        'ip_address'  => $data['ip_address'] ?? null,

        'created_at'  => $data['created_at'] ?? $this->now(),
      ],
      [
        '%d', // ticket_id

        '%d', // actor_id
        '%s', // actor_type

        '%s', // event_type

        '%s', // description

        '%s', // payload

        '%s', // ip_address

        '%s', // created_at
      ]
    );
  }

  /**
   * Find activity by ID
   */
  public function find(int $id): ?Activity {
    $row = $this->db->get_row(
      $this->db->prepare(
        "SELECT *
                 FROM {$this->table()}
                 WHERE id = %d",
        $id
      ),
      ARRAY_A
    );

    return $row
      ? $this->hydrate($row)
      : null;
  }

  /**
   * Get activities for a ticket
   *
   * @return Activity[]
   */
  public function getByTicket(int $ticketId): array {
    $results = $this->db->get_results(
      $this->db->prepare(
        "SELECT *
                 FROM {$this->table()}
                 WHERE ticket_id = %d
                 ORDER BY created_at ASC",
        $ticketId
      ),
      ARRAY_A
    );

    return array_map(
      fn(array $row) => $this->hydrate($row),
      $results
    );
  }

  /**
   * Get activities by actor
   *
   * @return Activity[]
   */
  public function getByActor(int $actorId): array {
    $results = $this->db->get_results(
      $this->db->prepare(
        "SELECT *
                 FROM {$this->table()}
                 WHERE actor_id = %d
                 ORDER BY created_at DESC",
        $actorId
      ),
      ARRAY_A
    );

    return array_map(
      fn(array $row) => $this->hydrate($row),
      $results
    );
  }

  /**
   * Get activities by event type
   *
   * @return Activity[]
   */
  public function getByEvent(ActivityType $eventType): array {
    $results = $this->db->get_results(
      $this->db->prepare(
        "SELECT *
                 FROM {$this->table()}
                 WHERE event_type = %s
                 ORDER BY created_at DESC",
        $eventType->value
      ),
      ARRAY_A
    );

    return array_map(
      fn(array $row) => $this->hydrate($row),
      $results
    );
  }

  /**
   * Hydrate row → Activity Entity
   */
  protected function hydrate(array $row): object {
    return new Activity(
      id: (int) $row['id'],

      ticketId: (int) $row['ticket_id'],

      actorId: isset($row['actor_id'])
        ? (int) $row['actor_id']
        : null,

      actorType: AuthorType::from(
        $row['actor_type']
      ),

      eventType: ActivityType::from(
        $row['event_type']
      ),

      description: $row['description'] ?? null,

      payload: $row['payload'] ?? null,

      ipAddress: $row['ip_address'] ?? null,

      createdAt: $row['created_at'],
    );
  }
}
