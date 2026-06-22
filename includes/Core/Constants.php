<?php

declare(strict_types=1);

namespace SupportBay\Core;

final class Constants {
  /**
   * Initialize all plugin constants
   */
  public static function define(): void {
    self::defineVersion();
    self::definePaths();
    self::defineUrls();
    self::defineMeta();
  }

  /**
   * Plugin version
   */
  private static function defineVersion(): void {
    if (! defined('SBAY_VERSION')) {
      define('SBAY_VERSION', '0.2.0');
    }

    if (! defined('SBAY_DB_VERSION')) {
      define('SBAY_DB_VERSION', '0.2.0');
    }
  }

  /**
   * Plugin file system paths
   */
  private static function definePaths(): void {
    if (! defined('SBAY_PLUGIN_FILE')) {
      define('SBAY_PLUGIN_FILE', dirname(__DIR__, 2) . '/supportbay.php');
    }

    if (! defined('SBAY_PLUGIN_PATH')) {
      define('SBAY_PLUGIN_PATH', plugin_dir_path(SBAY_PLUGIN_FILE));
    }

    if (! defined('SBAY_PLUGIN_DIR')) {
      define('SBAY_PLUGIN_DIR', SBAY_PLUGIN_PATH);
    }
  }

  /**
   * Plugin URLs
   */
  private static function defineUrls(): void {
    if (! defined('SBAY_PLUGIN_URL')) {
      define('SBAY_PLUGIN_URL', plugin_dir_url(SBAY_PLUGIN_FILE));
    }
  }

  /**
   * Meta information
   */
  private static function defineMeta(): void {
    if (! defined('SBAY_BASENAME')) {
      define('SBAY_BASENAME', plugin_basename(SBAY_PLUGIN_FILE));
    }
  }
}
