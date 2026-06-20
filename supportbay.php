<?php

/**
 * Plugin Name:       SupportBay
 * Plugin URI:        https://supportbay.io
 * Description:       Modern WordPress support system with ticketing, Envato verification, live chat, AI chatbot, and provider integrations.
 * Version:           0.1.0
 * Requires at least: 6.7
 * Requires PHP:      8.1
 * Author:            SupportBay Team
 * Author URI:        https://supportbay.io
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       supportbay
 * Domain Path:       /languages
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
  exit;
}

/**
 * Load Composer autoloader.
 */
require_once __DIR__ . '/vendor/autoload.php';


/**
 * Define constants FIRST
 */
\SupportBay\Core\Constants::define();


/**
 * Load helper functions (fallback).
 */
$functions = SBAY_PLUGIN_PATH . 'includes/functions.php';

if (file_exists($functions)) {
  require_once $functions;
}


/**
 * Plugin activation
 */
register_activation_hook(
  SBAY_PLUGIN_FILE,
  [\SupportBay\Core\Activator::class, 'activate']
);


/**
 * Plugin deactivation
 */
register_deactivation_hook(
  SBAY_PLUGIN_FILE,
  [\SupportBay\Core\Deactivator::class, 'deactivate']
);


/**
 * Boot SupportBay.
 */
(new SupportBay\Core\Application())->boot();
