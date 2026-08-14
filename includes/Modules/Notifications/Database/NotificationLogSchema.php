<?php

declare(strict_types=1);

namespace SupportBay\Modules\Notifications\Database;

use SupportBay\Modules\Notifications\Enums\NotificationStatus;

final class NotificationLogSchema {
  public static function tableName(): string {
    global $wpdb;

    return $wpdb->prefix . 'sbay_notification_logs';
  }

  public static function schema(): string {
    global $wpdb;

    return "CREATE TABLE " . self::tableName() . " (
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      ticket_id BIGINT UNSIGNED NULL,
      user_id BIGINT UNSIGNED NULL,
      channel VARCHAR(30) NOT NULL DEFAULT 'email',
      event VARCHAR(50) NOT NULL,
      recipient VARCHAR(255) NOT NULL,
      subject VARCHAR(255) NULL,
      payload LONGTEXT NULL,
      status VARCHAR(20) NOT NULL DEFAULT '" . NotificationStatus::PENDING->value . "',
      provider VARCHAR(50) NULL,
      provider_message_id VARCHAR(255) NULL,
      error_message TEXT NULL,
      retry_count INT UNSIGNED NOT NULL DEFAULT 0,
      scheduled_at DATETIME NULL,
      sent_at DATETIME NULL,
      delivered_at DATETIME NULL,
      metadata LONGTEXT NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      KEY ticket_id (ticket_id),
      KEY user_id (user_id),
      KEY channel (channel),
      KEY event (event),
      KEY status (status),
      KEY provider (provider),
      KEY scheduled_at (scheduled_at),
      KEY sent_at (sent_at),
      KEY created_at (created_at)
    ) {$wpdb->get_charset_collate()};";
  }
}
