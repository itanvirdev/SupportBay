<?php

declare(strict_types=1);

namespace SupportBay\Core;

final class Uninstaller {
  /**
   * Main uninstall handler
   */
  public static function uninstall(): void {
    if (! current_user_can('activate_plugins')) {
      return;
    }

    self::maybeRemoveOptions();
    self::maybeRemoveTransients();
    self::maybeClearCronJobs();
    self::logUninstall();
  }

  /**
   * Remove plugin options
   */
  private static function maybeRemoveOptions(): void {
    /**
     * We check future setting:
     * sbay_settings['delete_data_on_uninstall']
     */

    $settings = get_option('sbay_settings', []);

    $deleteData = $settings['delete_data_on_uninstall'] ?? false;

    if (! $deleteData) {
      return;
    }

    delete_option('sbay_settings');
    delete_option('sbay_version');

    /**
     * Future tables (example):
     *
     * global $wpdb;
     * $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}sbay_tickets");
     */
  }

  /**
   * Remove transients / cache
   */
  private static function maybeRemoveTransients(): void {
    /**
     * Future cleanup:
     *
     * delete_transient('sbay_*');
     */
  }

  /**
   * Clear scheduled cron jobs
   */
  private static function maybeClearCronJobs(): void {
    /**
     * Future:
     *
     * wp_clear_scheduled_hook('sbay_*');
     */
  }

  /**
   * Log uninstall event (debug only)
   */
  private static function logUninstall(): void {
    if (defined('WP_DEBUG') && WP_DEBUG) {
      error_log('[SupportBay] Plugin uninstalled: ' . SBAY_VERSION);
    }
  }
}
