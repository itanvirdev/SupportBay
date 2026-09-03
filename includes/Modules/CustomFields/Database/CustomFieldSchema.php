<?php

declare(strict_types=1);

namespace SupportBay\Modules\CustomFields\Database;

use SupportBay\Modules\CustomFields\Enums\CustomFieldStatus;
use SupportBay\Modules\CustomFields\Enums\CustomFieldType;

final class CustomFieldSchema {
  public static function tableName(): string {
    global $wpdb;
    return $wpdb->prefix . 'sbay_custom_fields';
  }

  public static function schema(): string {
    global $wpdb;
    return "CREATE TABLE " . self::tableName() . " (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      name VARCHAR(100) NOT NULL,
      slug VARCHAR(120) NOT NULL,
      type VARCHAR(30) NOT NULL DEFAULT '" . CustomFieldType::TEXT->value . "',
      options LONGTEXT NULL,
      placeholder VARCHAR(255) NULL,
      is_required TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
      form_location VARCHAR(20) NOT NULL DEFAULT 'ticket',
      audience VARCHAR(20) NOT NULL DEFAULT 'both',
      category_ids LONGTEXT NULL,
      status VARCHAR(20) NOT NULL DEFAULT '" . CustomFieldStatus::ACTIVE->value . "',
      sort_order INT UNSIGNED NOT NULL DEFAULT 0,
      created_at DATETIME NOT NULL,
      updated_at DATETIME NOT NULL,
      PRIMARY KEY  (id),
      UNIQUE KEY slug (slug),
      KEY type (type),
      KEY form_location (form_location),
      KEY status_sort (status, sort_order)
    ) {$wpdb->get_charset_collate()};";
  }
}
