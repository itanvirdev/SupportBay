<?php

declare(strict_types=1);

namespace SupportBay\Core\Database;

use RuntimeException;
use wpdb;
use SupportBay\Core\Entities\Entity;

abstract class Repository {
  /**
   * WordPress database instance.
   */
  protected wpdb $db;

  /**
   * Primary key column.
   */
  protected string $primaryKey = 'id';

  /**
   * Repository constructor.
   */
  public function __construct() {
    global $wpdb;

    $this->db = $wpdb;
  }

  /**
   * Database table.
   */
  abstract protected function table(): string;

  /**
   * Hydrate database row into entity.
   */
  abstract protected function hydrate(array $row): object;

  /**
   * Current datetime.
   */
  protected function now(): string {
    return current_time('mysql');
  }

  /**
   * Insert record.
   */
  protected function insert(array $data, array $formats = []): int {
    $result = $this->db->insert(
      $this->table(),
      $data,
      $formats
    );

    if ($result === false) {
      throw new RuntimeException(
        'Database insert failed: ' . $this->db->last_error
      );
    }

    return (int) $this->db->insert_id;
  }


  /**
   * Update by primary key.
   */
  protected function updateById(
    int $id,
    array $data,
    array $formats = []
  ): bool {
    $result = $this->db->update(
      $this->table(),
      $data,
      [$this->primaryKey => $id],
      $formats,
      ['%d']
    );

    if ($result === false) {
      throw new RuntimeException(
        'Database update failed: ' . $this->db->last_error
      );
    }

    return true;
  }

  /**
   * Delete by primary key.
   */
  protected function deleteById(int $id): bool {
    $result = $this->db->delete(
      $this->table(),
      [$this->primaryKey => $id],
      ['%d']
    );

    if ($result === false) {
      throw new RuntimeException(
        'Database delete failed: ' . $this->db->last_error
      );
    }

    return true;
  }

  /**
   * Find by primary key.
   */
  protected function findById(int $id): ?object {
    $row = $this->db->get_row(
      $this->db->prepare(
        "SELECT * FROM {$this->table()} WHERE {$this->primaryKey} = %d LIMIT 1",
        $id
      ),
      ARRAY_A
    );

    return $row ? $this->hydrate($row) : null;
  }

  /**
   * Get all records.
   */
  protected function findAll(
    string $orderBy = 'id',
    string $direction = 'ASC'
  ): array {
    $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';

    $rows = $this->db->get_results(
      "SELECT * FROM {$this->table()} ORDER BY {$orderBy} {$direction}",
      ARRAY_A
    );

    return array_map(
      fn(array $row) => $this->hydrate($row),
      $rows
    );
  }

  /**
   * Find first matching record.
   */
  protected function first(array $where): ?object {
    $query = $this->buildWhere($where);

    $row = $this->db->get_row(
      $this->db->prepare(
        "SELECT * FROM {$this->table()} {$query['sql']} LIMIT 1",
        ...$query['values']
      ),
      ARRAY_A
    );

    return $row ? $this->hydrate($row) : null;
  }


  /**
   * Find matching records.
   */
  protected function findWhere(
    array $where,
    string $orderBy = 'id',
    string $direction = 'ASC'
  ): array {
    $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';

    $query = $this->buildWhere($where);

    $rows = $this->db->get_results(
      $this->db->prepare(
        "SELECT *
                 FROM {$this->table()}
                 {$query['sql']}
                 ORDER BY {$orderBy} {$direction}",
        ...$query['values']
      ),
      ARRAY_A
    );

    return array_map(
      fn(array $row) => $this->hydrate($row),
      $rows
    );
  }

  /**
   * Check record exists.
   */
  protected function exists(array $where): bool {
    return $this->count($where) > 0;
  }

  /**
   * Count records.
   */
  protected function count(array $where = []): int {
    if (empty($where)) {
      return (int) $this->db->get_var(
        "SELECT COUNT(*) FROM {$this->table()}"
      );
    }

    $query = $this->buildWhere($where);

    return (int) $this->db->get_var(
      $this->db->prepare(
        "SELECT COUNT(*)
                 FROM {$this->table()}
                 {$query['sql']}",
        ...$query['values']
      )
    );
  }

  /**
   * Get single column value.
   */
  protected function value(
    string $column,
    array $where
  ): mixed {
    $query = $this->buildWhere($where);

    return $this->db->get_var(
      $this->db->prepare(
        "SELECT {$column}
                 FROM {$this->table()}
                 {$query['sql']}
                 LIMIT 1",
        ...$query['values']
      )
    );
  }

  /**
   * Build WHERE clause.
   */
  private function buildWhere(array $where): array {
    $clauses = [];
    $values = [];

    foreach ($where as $column => $value) {
      if (is_int($value)) {
        $clauses[] = "{$column} = %d";
      } elseif (is_float($value)) {
        $clauses[] = "{$column} = %f";
      } else {
        $clauses[] = "{$column} = %s";
      }

      $values[] = $value;
    }

    return [
      'sql' => 'WHERE ' . implode(' AND ', $clauses),
      'values' => $values,
    ];
  }


  /**
   * Create
   */
  public function create(array $data): int {
    return $this->insert($data);
  }

  /**
   * Update
   */
  public function update(int $id, array $data): bool {
    return $this->updateById($id, $data);
  }

  /**
   * Delete
   */
  public function delete(int $id): bool {
    return $this->deleteById($id);
  }

  /**
   * Find by ID.
   */
  public function find(int $id): ?Entity {
    return $this->findById($id);
  }

  /**
   * Find all
   */
  public function all(): array {
    return $this->findAll();
  }
}
