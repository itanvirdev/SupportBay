<?php

declare(strict_types=1);

namespace SupportBay\Modules\CustomFields\Database;

final class TicketCustomFieldValueSchema {
  public static function tableName(): string {
    global $wpdb;
    return $wpdb->prefix . 'sbay_ticket_custom_field_values';
  }

  public static function schema(): string {
    global $wpdb;
    return "CREATE TABLE " . self::tableName() . " (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      ticket_id BIGINT UNSIGNED NOT NULL,
      field_id BIGINT UNSIGNED NOT NULL,
      value LONGTEXT NOT NULL,
      updated_by BIGINT UNSIGNED NULL,
      created_at DATETIME NOT NULL,
      updated_at DATETIME NOT NULL,
      PRIMARY KEY  (id),
      UNIQUE KEY ticket_field (ticket_id, field_id),
      KEY field_id (field_id),
      KEY ticket_id (ticket_id)
    ) {$wpdb->get_charset_collate()};";
  }
}
