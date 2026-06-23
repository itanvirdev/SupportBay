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
   * @var Department|null
   */
  public function find(int $id): ?Department {
    return $this->findById($id);
  }

  /**
   * Find by slug
   * @var Department|null
   */
  public function findBySlug(string $slug): ?Department {
    return $this->first(
      'slug = %s',
      [$slug]
    );
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
    return $this->findAll('sort_order', 'ASC');
  }

  /**
   * Get active departments
   *
   * @return Department[]
   */
  public function getActive(): array {
    return $this->findWhere(
      'status = %s',
      ['active'],
      'sort_order'
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
