<?php

declare(strict_types=1);

namespace SupportBay\Modules\Departments\Database;

use SupportBay\Modules\Departments\Enums\DepartmentStatus;
use SupportBay\Modules\Tickets\Enums\TicketPriority;

final class DepartmentSchema {
  /**
   * Table name
   */
  public static function tableName(): string {
    global $wpdb;

    return $wpdb->prefix . 'sbay_departments';
  }

  /**
   * Database schema
   */
  public static function schema(): string {
    global $wpdb;

    return "CREATE TABLE " . self::tableName() . " (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

            name VARCHAR(100) NOT NULL,

            slug VARCHAR(120) NOT NULL,

            description TEXT NULL,

            status VARCHAR(20) NOT NULL DEFAULT '" . DepartmentStatus::ACTIVE->value . "',

            sort_order INT UNSIGNED NOT NULL DEFAULT 0,

            auto_assign_agent_id BIGINT UNSIGNED NULL,

            default_priority VARCHAR(20) NOT NULL DEFAULT '" . TicketPriority::NORMAL->value . "',

            color VARCHAR(20) NULL,

            icon VARCHAR(100) NULL,

            metadata LONGTEXT NULL,

            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

            PRIMARY KEY (id),

            UNIQUE KEY name (name),

            UNIQUE KEY slug (slug),

            KEY status (status),

            KEY sort_order (sort_order),

            KEY auto_assign_agent_id (auto_assign_agent_id),

            KEY default_priority (default_priority),

            KEY created_at (created_at)

        ) {$wpdb->get_charset_collate()};";
  }
}
