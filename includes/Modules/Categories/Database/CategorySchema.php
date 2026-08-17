<?php

declare(strict_types=1);

namespace SupportBay\Modules\Categories\Database;

use SupportBay\Modules\Categories\Enums\CategoryStatus;

final class CategorySchema {
  public static function tableName(): string {
    global $wpdb;
    return $wpdb->prefix . 'sbay_categories';
  }

  public static function schema(): string {
    global $wpdb;
    return "CREATE TABLE " . self::tableName() . " (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      name VARCHAR(100) NOT NULL,
      slug VARCHAR(120) NOT NULL,
      description TEXT NULL,
      department_id BIGINT UNSIGNED NULL,
      status VARCHAR(20) NOT NULL DEFAULT '" . CategoryStatus::ACTIVE->value . "',
      color VARCHAR(20) NULL,
      sort_order INT UNSIGNED NOT NULL DEFAULT 0,
      created_at DATETIME NOT NULL,
      updated_at DATETIME NOT NULL,
      PRIMARY KEY  (id),
      UNIQUE KEY slug (slug),
      KEY department_id (department_id),
      KEY status (status),
      KEY sort_order (sort_order)
    ) {$wpdb->get_charset_collate()};";
  }
}
