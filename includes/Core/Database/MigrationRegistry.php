<?php

declare(strict_types=1);

namespace SupportBay\Core\Database;

use SupportBay\Modules\Tickets\Database\TicketSchema;
use SupportBay\Modules\Messages\Database\MessageSchema;
use SupportBay\Modules\Departments\Database\DepartmentSchema;

final class MigrationRegistry {
  /**
   * Registered database tables.
   *
   * @return array<class-string>
   */
  public static function tables(): array {
    return [
      TicketSchema::class,
      MessageSchema::class,
      DepartmentSchema::class,

      // AttachmentSchema::class,
    ];
  }
}
