<?php

/**
 * Fired when plugin is uninstalled.
 * WordPress requires this file in plugin root.
 */

if (! defined('WP_UNINSTALL_PLUGIN')) {
  exit;
}

require_once __DIR__ . '/vendor/autoload.php';

SupportBay\Core\Uninstaller::uninstall();
