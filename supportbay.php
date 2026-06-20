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
 * Plugin version.
 */
define('SBAY_VERSION', '0.1.0');

/**
 * Plugin file.
 */
define('SBAY_PLUGIN_FILE', __FILE__);

/**
 * Plugin basename.
 */
define('SBAY_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Plugin path.
 */
define('SBAY_PLUGIN_PATH', plugin_dir_path(__FILE__));

/**
 * Plugin URL.
 */
define('SBAY_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Load Composer autoloader.
 */
$autoload = SBAY_PLUGIN_PATH . 'vendor/autoload.php';

if (file_exists($autoload)) {
  require_once $autoload;
}

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
