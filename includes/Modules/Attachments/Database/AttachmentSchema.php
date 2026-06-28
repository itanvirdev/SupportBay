<?php

declare(strict_types=1);

namespace SupportBay\Modules\Attachments\Database;

use SupportBay\Modules\Attachments\Enums\AttachmentCategory;
use SupportBay\Modules\Attachments\Enums\AttachmentState;
use SupportBay\Modules\Attachments\Enums\ScanStatus;
use SupportBay\Modules\Attachments\Enums\StorageDisk;

final class AttachmentSchema {
  /**
   * Table name.
   */
  public static function tableName(): string {
    global $wpdb;

    return $wpdb->prefix . 'sbay_attachments';
  }

  /**
   * Database schema.
   */
  public static function schema(): string {
    global $wpdb;

    return "CREATE TABLE " . self::tableName() . " (

            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

            message_id BIGINT UNSIGNED NOT NULL,

            ticket_id BIGINT UNSIGNED NOT NULL,

            uploaded_by_id BIGINT UNSIGNED NULL,

            uploaded_by_type VARCHAR(20) NOT NULL DEFAULT 'system',

            disk VARCHAR(50) NOT NULL DEFAULT '" . StorageDisk::LOCAL->value . "',

            original_name VARCHAR(255) NOT NULL,

            stored_name VARCHAR(255) NOT NULL,

            path TEXT NOT NULL,

            file_size BIGINT UNSIGNED NOT NULL DEFAULT 0,

            extension VARCHAR(20) NOT NULL,

            mime_type VARCHAR(150) NOT NULL,

            category VARCHAR(30) NOT NULL DEFAULT '" . AttachmentCategory::DOCUMENT->value . "',

            checksum CHAR(64) NULL,

            width INT UNSIGNED NULL,

            height INT UNSIGNED NULL,

            duration DECIMAL(10,2) NULL,

            is_previewable TINYINT(1) NOT NULL DEFAULT 0,

            scan_status VARCHAR(20) NOT NULL DEFAULT '" . ScanStatus::PENDING->value . "',

            state VARCHAR(20) NOT NULL DEFAULT '" . AttachmentState::ACTIVE->value . "',

            download_count INT UNSIGNED NOT NULL DEFAULT 0,

            last_accessed_at DATETIME NULL,

            metadata LONGTEXT NULL,

            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

            PRIMARY KEY (id),

            UNIQUE KEY stored_name (stored_name),

            KEY message_id (message_id),

            KEY ticket_id (ticket_id),

            KEY uploaded_by_id (uploaded_by_id),

            KEY uploaded_by_type (uploaded_by_type),

            KEY disk (disk),

            KEY file_size (file_size),

            KEY extension (extension),

            KEY mime_type (mime_type),

            KEY category (category),

            KEY checksum (checksum),

            KEY is_previewable (is_previewable),

            KEY scan_status (scan_status),

            KEY state (state),

            KEY last_accessed_at (last_accessed_at),

            KEY created_at (created_at)

        ) {$wpdb->get_charset_collate()};";
  }
}
