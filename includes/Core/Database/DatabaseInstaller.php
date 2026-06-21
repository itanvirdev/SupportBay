<?php

declare(strict_types=1);

namespace SupportBay\Core\Database;

final class DatabaseInstaller {
  public static function install(): void {
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    foreach (MigrationRegistry::tables() as $table) {
      dbDelta($table::schema());
    }

    update_option('sbay_db_version', SBAY_DB_VERSION);
  }
}
