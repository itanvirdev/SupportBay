<?php

declare(strict_types=1);

namespace SupportBay\Modules\Messages\Database;

use SupportBay\Modules\Messages\Enums\MessageType;
use SupportBay\Common\Enums\AuthorType;

final class MessageSchema {
  public static function tableName(): string {
    global $wpdb;

    return $wpdb->prefix . 'sbay_messages';
  }

  public static function schema(): string {
    global $wpdb;

    return "CREATE TABLE " . self::tableName() . " (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

            ticket_id BIGINT UNSIGNED NOT NULL,

            author_id BIGINT UNSIGNED NULL,
            author_type VARCHAR(20) NOT NULL DEFAULT '" . AuthorType::CUSTOMER->value . "',

            type VARCHAR(20) NOT NULL DEFAULT '" . MessageType::REPLY->value . "',

            content LONGTEXT NOT NULL,

            edited_by_id BIGINT UNSIGNED NULL,
            edited_at DATETIME NULL,

            customer_read_at DATETIME NULL,
            staff_read_at DATETIME NULL,

            metadata LONGTEXT NULL,

            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP,

            KEY ticket_id (ticket_id),
            KEY author_id (author_id),
            KEY author_type (author_type),
            KEY type (type),
            KEY edited_at (edited_at),
            KEY customer_read_at (customer_read_at),
            KEY staff_read_at (staff_read_at),
            KEY created_at (created_at)

        ) {$wpdb->get_charset_collate()};";
  }
}
