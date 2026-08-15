<?php

declare(strict_types=1);

namespace SupportBay\Modules\Tickets\Database;

final class TicketSlaBreachSchema {
  public static function tableName(): string {
    global $wpdb;
    return $wpdb->prefix . 'sbay_ticket_sla_breaches';
  }

  public static function schema(): string {
    global $wpdb;
    return "CREATE TABLE " . self::tableName() . " (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      ticket_id BIGINT UNSIGNED NOT NULL,
      metric VARCHAR(50) NOT NULL,
      target_minutes INT UNSIGNED NOT NULL,
      breached_at DATETIME NOT NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY  (id),
      UNIQUE KEY ticket_metric (ticket_id, metric),
      KEY metric (metric),
      KEY breached_at (breached_at)
    ) {$wpdb->get_charset_collate()};";
  }
}
