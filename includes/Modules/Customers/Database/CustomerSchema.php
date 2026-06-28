<?php

declare(strict_types=1);

namespace SupportBay\Modules\Customers\Database;

use SupportBay\Modules\Customers\Enums\CustomerSource;
use SupportBay\Modules\Customers\Enums\CustomerState;

final class CustomerSchema {
  /**
   * Table name.
   */
  public static function tableName(): string {
    global $wpdb;

    return $wpdb->prefix . 'sbay_customers';
  }

  /**
   * Database schema.
   */
  public static function schema(): string {
    global $wpdb;

    return "CREATE TABLE " . self::tableName() . " (

        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

        user_id BIGINT UNSIGNED NOT NULL,

        state VARCHAR(20) NOT NULL DEFAULT '" . CustomerState::default()->value . "',

        source VARCHAR(30) NOT NULL DEFAULT '" . CustomerSource::default()->value . "',

        avatar_url TEXT NULL,

        company VARCHAR(150) NULL,

        phone VARCHAR(50) NULL,

        country VARCHAR(100) NULL,

        timezone VARCHAR(100) NULL,

        language VARCHAR(20) NULL,

        last_login_at DATETIME NULL,

        metadata LONGTEXT NULL,

        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ON UPDATE CURRENT_TIMESTAMP,

        PRIMARY KEY (id),

        UNIQUE KEY user_id (user_id),

        KEY state (state),

        KEY source (source),

        KEY last_login_at (last_login_at),

        KEY created_at (created_at)

    ) {$wpdb->get_charset_collate()};";
  }
}
