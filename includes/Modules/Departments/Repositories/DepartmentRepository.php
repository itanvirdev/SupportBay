<?php

declare(strict_types=1);

namespace SupportBay\Modules\Departments\Repositories;

use SupportBay\Core\Database\Repository;
use SupportBay\Modules\Departments\Database\DepartmentSchema;
use SupportBay\Modules\Departments\Entities\Department;
use SupportBay\Modules\Departments\Enums\DepartmentStatus;
use SupportBay\Modules\Tickets\Enums\TicketPriority;

final class DepartmentRepository extends Repository {

  /**
   * Table
   */
  protected function table(): string {
    return DepartmentSchema::tableName();
  }

  /**
   * Create department
   */
  public function create(array $data): int {
    return $this->insert(
      [
        'name'                 => $data['name'],
        'slug'                 => $data['slug'],
        'description'          => $data['description'] ?? null,
        'status'               => $data['status'],
        'sort_order'           => $data['sort_order'] ?? 0,
        'auto_assign_agent_id' => $data['auto_assign_agent_id'] ?? null,
        'default_priority'     => $data['default_priority'],
        'color'                => $data['color'] ?? null,
        'icon'                 => $data['icon'] ?? null,
        'metadata'             => $data['metadata'] ?? null,
        'created_at'           => $data['created_at'] ?? $this->now(),
        'updated_at'           => $data['updated_at'] ?? $this->now(),
      ],
      [
        '%s', // name
        '%s', // slug
        '%s', // description
        '%s', // status
        '%d', // sort_order
        '%d', // auto_assign_agent_id
        '%s', // default_priority
        '%s', // color
        '%s', // icon
        '%s', // metadata
        '%s', // created_at
        '%s', // updated_at
      ]
    );
  }

  /**
   * Find by ID
   */
  public function find(int $id): ?Department {
    $row = $this->findById($id);

    return $row
      ? $this->hydrate($row)
      : null;
  }

  /**
   * Find by slug
   */
  public function findBySlug(string $slug): ?Department {
    $row = $this->db->get_row(
      $this->db->prepare(
        "SELECT *
        FROM {$this->table()}
        WHERE slug = %s
        LIMIT 1",
        $slug
      ),
      ARRAY_A
    );

    return $row
      ? $this->hydrate($row)
      : null;
  }

  /**
   * Find by name
   */
  public function findByName(string $name): ?Department {
    $row = $this->db->get_row(
      $this->db->prepare(
        "SELECT *
        FROM {$this->table()}
        WHERE name = %s
        LIMIT 1",
        $name
      ),
      ARRAY_A
    );

    return $row
      ? $this->hydrate($row)
      : null;
  }

  /**
   * Get all departments
   *
   * @return Department[]
   */
  public function getAll(): array {
    $rows = $this->db->get_results(
      "SELECT *
      FROM {$this->table()}
      ORDER BY sort_order ASC, name ASC",
      ARRAY_A
    );

    return array_map(
      fn(array $row) => $this->hydrate($row),
      $rows
    );
  }

  /**
   * Get active departments
   *
   * @return Department[]
   */
  public function getActive(): array {
    $rows = $this->db->get_results(
      $this->db->prepare(
        "SELECT *
        FROM {$this->table()}
        WHERE status = %s
        ORDER BY sort_order ASC, name ASC",
        DepartmentStatus::ACTIVE->value
      ),
      ARRAY_A
    );

    return array_map(
      fn(array $row) => $this->hydrate($row),
      $rows
    );
  }

  /**
   * Update department
   */
  public function update(int $id, array $data): bool {
    $data['updated_at'] = $this->now();

    return $this->updateById($id, $data);
  }

  /**
   * Delete department
   */
  public function delete(int $id): bool {
    return $this->deleteById($id);
  }

  /**
   * Hydrate row to entity
   */
  protected function hydrate(array $row): object {
    return new Department(
      id: (int) $row['id'],
      name: $row['name'],
      slug: $row['slug'],
      description: $row['description'],
      status: DepartmentStatus::from($row['status']),
      sortOrder: (int) $row['sort_order'],
      autoAssignAgentId: isset($row['auto_assign_agent_id'])
        ? (int) $row['auto_assign_agent_id']
        : null,
      defaultPriority: TicketPriority::from($row['default_priority']),
      color: $row['color'],
      icon: $row['icon'],
      metadata: $row['metadata'],
      createdAt: $row['created_at'],
      updatedAt: $row['updated_at'],
    );
  }
}
