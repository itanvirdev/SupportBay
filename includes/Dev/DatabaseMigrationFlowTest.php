<?php

declare(strict_types=1);

namespace SupportBay\Dev;

use SupportBay\Core\Database\DatabaseInstaller;
use SupportBay\Core\Database\MigrationRegistry;
use SupportBay\Core\Testing\Assert;
use SupportBay\Core\Testing\FlowTest;

final class DatabaseMigrationFlowTest extends FlowTest {
  protected static function title(): string {
    return 'Database Migration Flow Test';
  }

  protected static function execute(...$services): void {
    global $wpdb;

    $previousSuppression = $wpdb->suppress_errors(true);

    try {
      DatabaseInstaller::install();
      DatabaseInstaller::install();
    } finally {
      $wpdb->suppress_errors($previousSuppression);
    }

    Assert::equals(
      SBAY_DB_VERSION,
      get_option('sbay_db_version'),
      'Database version is stored after successful migrations.'
    );

    foreach (MigrationRegistry::tables() as $schema) {
      Assert::true(
        preg_match('/PRIMARY KEY[^\r\n]*(?:UNIQUE KEY|,\s*KEY)/i', $schema::schema()) !== 1,
        sprintf('%s declares each database key on its own line.', $schema),
      );

      $table = $schema::tableName();
      $installed = $wpdb->get_var($wpdb->prepare(
        'SHOW TABLES LIKE %s',
        $table,
      ));

      Assert::equals(
        $table,
        $installed,
        sprintf('%s exists after repeatable installation.', $table),
      );
    }

    Assert::equals(
      '',
      $wpdb->last_error,
      'Repeated database installation completes without SQL errors.'
    );

    $trackColumn=$wpdb->get_row('SHOW COLUMNS FROM '.\SupportBay\Modules\Tickets\Database\TicketSchema::tableName()." LIKE 'track_id'",ARRAY_A);
    Assert::true(is_array($trackColumn)&&strtolower((string)($trackColumn['Type']??''))==='varchar(64)','Ticket track IDs support configurable sequential prefixes and lengths.');
  }
}
