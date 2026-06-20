<?php

declare(strict_types=1);

namespace SupportBay\Core;

final class Activator {
  /**
   * Plugin activation handler
   */
  public static function activate(): void {
    self::storeVersion();
    self::createDefaultOptions();
    self::logActivation();
  }

  /**
   * Store current plugin version in DB
   */
  private static function storeVersion(): void {
    if (! get_option('sbay_version')) {
      add_option('sbay_version', SBAY_VERSION);
    } else {
      update_option('sbay_version', SBAY_VERSION);
    }
  }

  /**
   * Create default plugin options
   */
  private static function createDefaultOptions(): void {
    $defaults = [
      'ticket_reopen_days' => 30,
      'default_department' => 'general-support',
      'file_upload_enabled' => true,
      'rich_text_enabled' => false,
    ];

    if (! get_option('sbay_settings')) {
      add_option('sbay_settings', $defaults);
    }
  }

  /**
   * Log activation event (for debugging / analytics later)
   */
  private static function logActivation(): void {
    if (defined('WP_DEBUG') && WP_DEBUG) {
      error_log('[SupportBay] Plugin activated: ' . SBAY_VERSION);
    }
  }
}
