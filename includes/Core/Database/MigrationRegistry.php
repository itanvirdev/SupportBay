<?php

declare(strict_types=1);

namespace SupportBay\Core\Database;

use SupportBay\Modules\Tickets\Database\TicketSchema;
use SupportBay\Modules\Messages\Database\MessageSchema;
use SupportBay\Modules\Departments\Database\DepartmentSchema;
use SupportBay\Modules\Activities\Database\ActivitySchema;
use SupportBay\Modules\Attachments\Database\AttachmentSchema;
use SupportBay\Modules\Customers\Database\CustomerSchema;
use SupportBay\Modules\Auth\Database\AuthTokenSchema;
use SupportBay\Modules\Providers\Database\ProviderSchema;

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
      ActivitySchema::class,
      AttachmentSchema::class,
      CustomerSchema::class,
      AuthTokenSchema::class,
      ProviderSchema::class,
    ];
  }
}
