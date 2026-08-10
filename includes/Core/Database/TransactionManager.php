<?php

declare(strict_types=1);

namespace SupportBay\Core\Database;

use Throwable;

final class TransactionManager {
  public function run(callable $operation): mixed {
    global $wpdb;

    $wpdb->query('START TRANSACTION');

    try {
      $result = $operation();
      $wpdb->query('COMMIT');

      return $result;
    } catch (Throwable $exception) {
      $wpdb->query('ROLLBACK');
      throw $exception;
    }
  }
}
