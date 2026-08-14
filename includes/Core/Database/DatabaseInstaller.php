<?php

declare(strict_types=1);

namespace SupportBay\Core\Database;

use RuntimeException;

final class DatabaseInstaller {
  public static function install(): void {
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    global $wpdb;

    foreach (MigrationRegistry::tables() as $table) {
      $wpdb->last_error = '';
      dbDelta($table::schema());

      if ($wpdb->last_error !== '') {
        throw new RuntimeException(
          sprintf(
            'Database migration failed for %s: %s',
            $table::tableName(),
            $wpdb->last_error,
          )
        );
      }

      $installed = $wpdb->get_var($wpdb->prepare(
        'SHOW TABLES LIKE %s',
        $table::tableName(),
      ));

      if ($installed !== $table::tableName()) {
        throw new RuntimeException(
          sprintf(
            'Database migration did not create %s.',
            $table::tableName(),
          )
        );
      }
    }

    update_option('sbay_db_version', SBAY_DB_VERSION);
  }
}
