<?php

declare(strict_types=1);

namespace SupportBay\Core\Database;

use SupportBay\Modules\Tickets\Database\TicketSchema;
use SupportBay\Modules\Messages\Database\MessageSchema;
use SupportBay\Modules\Activities\Database\ActivitySchema;
use SupportBay\Modules\Attachments\Database\AttachmentSchema;
use SupportBay\Modules\Customers\Database\CustomerSchema;
use SupportBay\Modules\Auth\Database\AuthTokenSchema;
use SupportBay\Modules\Providers\Database\ProviderSchema;
use SupportBay\Modules\Verifications\Database\PurchaseVerificationSchema;
use SupportBay\Modules\SavedReplies\Database\SavedReplySchema;
use SupportBay\Modules\Categories\Database\CategorySchema;
use SupportBay\Modules\Tags\Database\TagSchema;
use SupportBay\Modules\Tags\Database\TicketTagSchema;
use SupportBay\Modules\CustomFields\Database\CustomFieldSchema;
use SupportBay\Modules\CustomFields\Database\TicketCustomFieldValueSchema;
use SupportBay\Modules\AssignRules\Database\AssignRuleSchema;

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
      ActivitySchema::class,
      AttachmentSchema::class,
      CustomerSchema::class,
      AuthTokenSchema::class,
      ProviderSchema::class,
      PurchaseVerificationSchema::class,
      SavedReplySchema::class,
      CategorySchema::class,
      TagSchema::class,
      TicketTagSchema::class,
      CustomFieldSchema::class,
      TicketCustomFieldValueSchema::class,
      AssignRuleSchema::class,
    ];
  }
}
