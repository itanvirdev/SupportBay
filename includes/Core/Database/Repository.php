<?php

declare(strict_types=1);

namespace SupportBay\Core\Database;

use RuntimeException;
use wpdb;

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
   * Get the table name.
   */
  abstract protected function table(): string;

  /**
   * Current WordPress datetime.
   */
  protected function now(): string {
    return current_time('mysql');
  }

  /**
   * Insert a record.
   *
   * Returns the inserted ID.
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
   * Update a record by primary key.
   */
  protected function updateById(
    int $id,
    array $data,
    array $formats = []
  ): bool {
    $result = $this->db->update(
      $this->table(),
      $data,
      [
        $this->primaryKey => $id,
      ],
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
   * Delete a record by primary key.
   */
  protected function deleteById(int $id): bool {
    $result = $this->db->delete(
      $this->table(),
      [
        $this->primaryKey => $id,
      ],
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
   * Check if a record exists.
   */
  public function exists(int $id): bool {
    $count = (int) $this->db->get_var(
      $this->db->prepare(
        "SELECT COUNT(*) FROM {$this->table()} WHERE {$this->primaryKey} = %d",
        $id
      )
    );

    return $count > 0;
  }

  /**
   * Count all records.
   */
  public function count(): int {
    return (int) $this->db->get_var(
      "SELECT COUNT(*) FROM {$this->table()}"
    );
  }
}
