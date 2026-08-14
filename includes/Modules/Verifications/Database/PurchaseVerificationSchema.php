<?php

declare(strict_types=1);

namespace SupportBay\Modules\Verifications\Database;

use SupportBay\Modules\Verifications\Enums\VerificationStatus;

final class PurchaseVerificationSchema {
  /**
   * Get table name.
   */
  public static function tableName(): string {
    global $wpdb;

    return $wpdb->prefix . 'sbay_purchase_verifications';
  }

  /**
   * Database schema.
   */
  public static function schema(): string {
    global $wpdb;

    $verificationStatus = VerificationStatus::PENDING->value;

    return "CREATE TABLE " . self::tableName() . " (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      provider VARCHAR(50) NOT NULL,
      provider_reference VARCHAR(255) NOT NULL,
      customer_id BIGINT UNSIGNED NULL,
      provider_customer_reference VARCHAR(255) NULL,
      product_id VARCHAR(255) NULL,
      product_name VARCHAR(255) NULL,
      license_type VARCHAR(100) NULL,
      support_expires_at DATETIME NULL,
      purchased_at DATETIME NULL,
      verified_at DATETIME NULL,
      last_checked_at DATETIME NULL,
      verification_status VARCHAR(20) NOT NULL DEFAULT '{$verificationStatus}',
      provider_snapshot LONGTEXT NULL,
      metadata LONGTEXT NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY  (id),
      UNIQUE KEY provider_reference (provider, provider_reference),
      KEY customer_id (customer_id),
      KEY provider_customer_reference (provider_customer_reference),
      KEY product_id (product_id),
      KEY license_type (license_type),
      KEY verification_status (verification_status),
      KEY support_expires_at (support_expires_at),
      KEY verified_at (verified_at),
      KEY last_checked_at (last_checked_at),
      KEY created_at (created_at),
      KEY updated_at (updated_at)
    ) {$wpdb->get_charset_collate()};";
  }
}
