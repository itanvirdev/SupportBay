<?php

declare(strict_types=1);

namespace SupportBay\Modules\Tags\Database;

use SupportBay\Modules\Tags\Enums\TagStatus;

final class TagSchema {
  public static function tableName(): string {
    global $wpdb;
    return $wpdb->prefix . 'sbay_tags';
  }

  public static function schema(): string {
    global $wpdb;
    return "CREATE TABLE " . self::tableName() . " (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      name VARCHAR(150) NOT NULL,
      slug VARCHAR(120) NOT NULL,
      color VARCHAR(20) NULL,
      show_on VARCHAR(20) NOT NULL DEFAULT 'both',
      status VARCHAR(20) NOT NULL DEFAULT '" . TagStatus::ACTIVE->value . "',
      created_at DATETIME NOT NULL,
      updated_at DATETIME NOT NULL,
      PRIMARY KEY  (id),
      UNIQUE KEY slug (slug),
      KEY status (status),
      KEY show_on (show_on)
    ) {$wpdb->get_charset_collate()};";
  }
}
