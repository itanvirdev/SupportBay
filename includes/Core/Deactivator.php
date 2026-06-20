<?php

declare(strict_types=1);

namespace SupportBay\Core;

final class Deactivator {
  /**
   * Plugin deactivation handler
   */
  public static function deactivate(): void {
    self::clearScheduledTasks();
    self::resetRuntimeCache();
    self::logDeactivation();
  }

  /**
   * Clear scheduled cron jobs (future use)
   */
  private static function clearScheduledTasks(): void {
    /**
     * Example (future implementation):
     *
     * wp_clear_scheduled_hook('sbay_ticket_cleanup');
     * wp_clear_scheduled_hook('sbay_notification_dispatch');
     */
  }

  /**
   * Reset runtime cache or transient data
   */
  private static function resetRuntimeCache(): void {
    /**
     * Example (future implementation):
     *
     * delete_transient('sbay_cache_*');
     */
  }

  /**
   * Log deactivation event (debugging only)
   */
  private static function logDeactivation(): void {
    if (defined('WP_DEBUG') && WP_DEBUG) {
      error_log('[SupportBay] Plugin deactivated: ' . SBAY_VERSION);
    }
  }
}
