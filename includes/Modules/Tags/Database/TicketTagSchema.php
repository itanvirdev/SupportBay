<?php

declare(strict_types=1);

namespace SupportBay\Modules\Tags\Database;

final class TicketTagSchema {
  public static function tableName(): string {
    global $wpdb;
    return $wpdb->prefix . 'sbay_ticket_tags';
  }

  public static function schema(): string {
    global $wpdb;
    return "CREATE TABLE " . self::tableName() . " (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      ticket_id BIGINT UNSIGNED NOT NULL,
      tag_id BIGINT UNSIGNED NOT NULL,
      assigned_by BIGINT UNSIGNED NULL,
      created_at DATETIME NOT NULL,
      PRIMARY KEY  (id),
      UNIQUE KEY ticket_tag (ticket_id, tag_id),
      KEY ticket_id (ticket_id),
      KEY tag_id (tag_id)
    ) {$wpdb->get_charset_collate()};";
  }
}
