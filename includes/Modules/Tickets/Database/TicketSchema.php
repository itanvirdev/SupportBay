<?php

declare(strict_types=1);

namespace SupportBay\Modules\Tickets\Database;

final class TicketSchema {
  public static function tableName(): string {
    global $wpdb;
    return $wpdb->prefix . 'sbay_tickets';
  }

  public static function schema(): string {
    global $wpdb;

    return "CREATE TABLE " . self::tableName() . " (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

            track_id VARCHAR(32) NOT NULL,

            customer_id BIGINT UNSIGNED NULL,

            created_by_id BIGINT UNSIGNED NULL,

            created_by_type VARCHAR(20) NOT NULL DEFAULT 'customer',

            purchase_verification_id BIGINT UNSIGNED NULL,

            department_id BIGINT UNSIGNED NOT NULL,

            assigned_agent_id BIGINT UNSIGNED NULL,

            subject VARCHAR(255) NOT NULL,

            status VARCHAR(50) NOT NULL DEFAULT 'open',

            state VARCHAR(50) NOT NULL DEFAULT 'active',

            priority VARCHAR(50) NOT NULL DEFAULT 'normal',

            source VARCHAR(30) NOT NULL DEFAULT 'web',

            last_message_id BIGINT UNSIGNED NULL,

            last_reply_at DATETIME NULL,

            first_response_at DATETIME NULL,

            resolved_at DATETIME NULL,

            closed_at DATETIME NULL,

            reopened_at DATETIME NULL,

            is_public TINYINT(1) NOT NULL DEFAULT 0,

            public_token CHAR(64) NULL,

            metadata LONGTEXT NULL,

            created_at DATETIME NOT NULL,

            updated_at DATETIME NULL,

            UNIQUE KEY track_id (track_id),

            KEY customer_id (customer_id),
            KEY department_id (department_id),
            KEY purchase_verification_id (purchase_verification_id),
            KEY assigned_agent_id (assigned_agent_id),
            KEY last_message_id (last_message_id),
            KEY last_reply_at (last_reply_at),
            KEY created_at (created_at),
            KEY status (status),
            KEY state (state),
            KEY priority (priority),
            KEY source (source)
        ) {$wpdb->get_charset_collate()};";
  }
}
