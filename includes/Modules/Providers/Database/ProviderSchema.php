<?php

declare(strict_types=1);

namespace SupportBay\Modules\Providers\Database;

use SupportBay\Modules\Providers\Enums\ProviderCategory;
use SupportBay\Modules\Providers\Enums\ProviderStatus;

final class ProviderSchema {
  /**
   * Database table name.
   */
  public static function tableName(): string {
    global $wpdb;

    return $wpdb->prefix . 'sbay_providers';
  }

  /**
   * Database schema.
   */
  public static function schema(): string {
    global $wpdb;

    return "CREATE TABLE " . self::tableName() . " (

      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

      slug VARCHAR(100) NOT NULL,

      name VARCHAR(150) NOT NULL,

      category VARCHAR(50) NOT NULL
        DEFAULT '" . ProviderCategory::MARKETPLACE->value . "',

      version VARCHAR(30) NULL,

      status VARCHAR(20) NOT NULL
        DEFAULT '" . ProviderStatus::DISABLED->value . "',

      settings LONGTEXT NULL,

      last_connected_at DATETIME NULL,

      last_error TEXT NULL,

      metadata LONGTEXT NULL,

      created_at DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

      updated_at DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

      UNIQUE KEY slug (slug),

      KEY category (category),

      KEY status (status),

      KEY last_connected_at (last_connected_at),

      KEY created_at (created_at),

      KEY updated_at (updated_at)

    ) {$wpdb->get_charset_collate()};";
  }
}
