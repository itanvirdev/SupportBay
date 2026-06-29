<?php

declare(strict_types=1);

namespace SupportBay\Modules\Auth\Database;

use SupportBay\Modules\Auth\Enums\AuthTokenState;
use SupportBay\Modules\Auth\Enums\AuthTokenType;

final class AuthTokenSchema {
  /**
   * Database table name.
   */
  public static function tableName(): string {
    global $wpdb;

    return $wpdb->prefix . 'sbay_auth_tokens';
  }

  /**
   * Database schema.
   */
  public static function schema(): string {
    global $wpdb;

    return "CREATE TABLE " . self::tableName() . " (

            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

            user_id BIGINT UNSIGNED NOT NULL,

            type VARCHAR(30) NOT NULL DEFAULT '" . AuthTokenType::MAGIC_LOGIN->value . "',

            state VARCHAR(20) NOT NULL DEFAULT '" . AuthTokenState::ACTIVE->value . "',

            token_hash CHAR(64) NOT NULL,

            redirect_to VARCHAR(255) NULL,

            expires_at DATETIME NOT NULL,

            last_used_at DATETIME NULL,

            revoked_at DATETIME NULL,

            revoked_by BIGINT UNSIGNED NULL,

            use_count INT UNSIGNED NOT NULL DEFAULT 0,

            max_uses INT UNSIGNED NULL,

            ip_address VARCHAR(45) NULL,

            user_agent TEXT NULL,

            metadata LONGTEXT NULL,

            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

            UNIQUE KEY token_hash (token_hash),

            KEY user_id (user_id),
            KEY type (type),
            KEY state (state),
            KEY expires_at (expires_at),
            KEY revoked_at (revoked_at),
            KEY last_used_at (last_used_at),
            KEY created_at (created_at)

        ) {$wpdb->get_charset_collate()};";
  }
}
