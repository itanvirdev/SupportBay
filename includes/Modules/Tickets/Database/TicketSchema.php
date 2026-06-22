<?php

declare(strict_types=1);

namespace SupportBay\Modules\Tickets\Database;

use SupportBay\Common\Enums\AuthorType;
use SupportBay\Common\Enums\SourceType;
use SupportBay\Modules\Tickets\Enums\TicketPriority;
use SupportBay\Modules\Tickets\Enums\TicketState;
use SupportBay\Modules\Tickets\Enums\TicketStatus;

final class TicketSchema {
  /**
   * Get table name.
   */
  public static function tableName(): string {
    global $wpdb;

    return $wpdb->prefix . 'sbay_tickets';
  }

  /**
   * Database schema.
   */
  public static function schema(): string {
    global $wpdb;

    $authorType = AuthorType::CUSTOMER->value;
    $status     = TicketStatus::OPEN->value;
    $state      = TicketState::ACTIVE->value;
    $priority   = TicketPriority::NORMAL->value;
    $source     = SourceType::WEB->value;

    return "CREATE TABLE " . self::tableName() . " (
 
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

            track_id CHAR(9) NOT NULL,

            customer_id BIGINT UNSIGNED NOT NULL,

            created_by_id BIGINT UNSIGNED NULL,

            created_by_type VARCHAR(20) NOT NULL DEFAULT '{$authorType}',

            purchase_verification_id BIGINT UNSIGNED NULL,

            department_id BIGINT UNSIGNED NOT NULL,

            assigned_agent_id BIGINT UNSIGNED NULL,

            subject VARCHAR(255) NOT NULL,

            status VARCHAR(20) NOT NULL DEFAULT '{$status}',

            state VARCHAR(20) NOT NULL DEFAULT '{$state}',

            priority VARCHAR(20) NOT NULL DEFAULT '{$priority}',

            source VARCHAR(30) NOT NULL DEFAULT '{$source}',

            last_message_id BIGINT UNSIGNED NULL,

            last_reply_at DATETIME NULL,

            first_response_at DATETIME NULL,

            resolved_at DATETIME NULL,

            closed_at DATETIME NULL,

            reopened_at DATETIME NULL,

            is_public TINYINT(1) NOT NULL DEFAULT 0,

            public_token CHAR(64) NULL,

            metadata LONGTEXT NULL,

            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

            PRIMARY KEY  (id),

            UNIQUE KEY track_id (track_id),

            KEY customer_id (customer_id),
            KEY created_by_id (created_by_id),
            KEY purchase_verification_id (purchase_verification_id),
            KEY department_id (department_id),
            KEY assigned_agent_id (assigned_agent_id),

            KEY status (status),
            KEY state (state),
            KEY priority (priority),
            KEY source (source),

            KEY last_message_id (last_message_id),
            KEY last_reply_at (last_reply_at),

            KEY created_at (created_at),
            KEY updated_at (updated_at)

        ) {$wpdb->get_charset_collate()};";
  }
}
