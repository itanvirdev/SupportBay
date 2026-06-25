<?php

declare(strict_types=1);

namespace SupportBay\Modules\Attachments\Database;

use SupportBay\Core\Database\Schema;

final class AttachmentSchema extends Schema {
  /**
   * Table name.
   */
  protected string $table = 'sbay_attachments';

  /**
   * Table schema.
   */
  protected function schema(): string {
    return "
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

        message_id BIGINT UNSIGNED NOT NULL,
        ticket_id BIGINT UNSIGNED NOT NULL,

        uploaded_by BIGINT UNSIGNED NULL,

        disk VARCHAR(50) NOT NULL DEFAULT 'local',

        original_name VARCHAR(255) NOT NULL,
        stored_name VARCHAR(255) NOT NULL,

        path TEXT NOT NULL,

        file_size BIGINT UNSIGNED NOT NULL DEFAULT 0,

        extension VARCHAR(20) NOT NULL,
        mime_type VARCHAR(150) NOT NULL,

        category VARCHAR(30) NOT NULL DEFAULT 'document',

        checksum CHAR(64) NULL,

        width INT UNSIGNED NULL,
        height INT UNSIGNED NULL,

        duration DECIMAL(10,2) NULL,

        is_previewable TINYINT(1) NOT NULL DEFAULT 0,

        scan_status VARCHAR(20) NOT NULL DEFAULT 'pending',

        state VARCHAR(20) NOT NULL DEFAULT 'active',

        download_count INT UNSIGNED NOT NULL DEFAULT 0,

        last_accessed_at DATETIME NULL,

        metadata LONGTEXT NULL,

        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ON UPDATE CURRENT_TIMESTAMP,

        PRIMARY KEY (id),

        UNIQUE KEY stored_name (stored_name),

        KEY message_id (message_id),
        KEY ticket_id (ticket_id),

        KEY uploaded_by (uploaded_by),

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
        ";
  }
}
