<?php

declare(strict_types=1);

namespace SupportBay\Modules\Activities\Database;

use SupportBay\Common\Enums\AuthorType;
use SupportBay\Modules\Activities\Enums\ActivityType;

final class ActivitySchema {
  public static function tableName(): string {
    global $wpdb;

    return $wpdb->prefix . 'sbay_activities';
  }

  public static function schema(): string {
    global $wpdb;

    return "CREATE TABLE " . self::tableName() . " (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

            ticket_id BIGINT UNSIGNED NOT NULL,

            actor_id BIGINT UNSIGNED NULL,

            actor_type VARCHAR(20) NOT NULL DEFAULT '" . AuthorType::SYSTEM->value . "',

            event_type VARCHAR(50) NOT NULL DEFAULT '" . ActivityType::TICKET_CREATED->value . "',

            description TEXT NULL,

            payload LONGTEXT NULL,

            ip_address VARCHAR(45) NULL,

            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

            KEY ticket_id (ticket_id),
            KEY actor_id (actor_id),
            KEY actor_type (actor_type),
            KEY event_type (event_type),
            KEY created_at (created_at)

        ) {$wpdb->get_charset_collate()};";
  }
}
