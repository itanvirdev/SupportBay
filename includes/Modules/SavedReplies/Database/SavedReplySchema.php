<?php

declare(strict_types=1);

namespace SupportBay\Modules\SavedReplies\Database;

use SupportBay\Modules\SavedReplies\Enums\SavedReplyStatus;

final class SavedReplySchema {
  public static function tableName(): string {
    global $wpdb;
    return $wpdb->prefix . 'sbay_saved_replies';
  }

  public static function schema(): string {
    global $wpdb;
    return "CREATE TABLE " . self::tableName() . " (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      title VARCHAR(190) NOT NULL,
      content LONGTEXT NOT NULL,
      category VARCHAR(100) NULL,
      status VARCHAR(20) NOT NULL DEFAULT '" . SavedReplyStatus::ACTIVE->value . "',
      created_by BIGINT UNSIGNED NOT NULL,
      usage_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
      last_used_at DATETIME NULL,
      last_used_by BIGINT UNSIGNED NULL,
      created_at DATETIME NOT NULL,
      updated_at DATETIME NOT NULL,
      PRIMARY KEY  (id),
      KEY status (status),
      KEY category (category),
      KEY created_by (created_by),
      KEY usage_count (usage_count),
      KEY last_used_at (last_used_at),
      KEY title (title)
    ) {$wpdb->get_charset_collate()};";
  }
}
