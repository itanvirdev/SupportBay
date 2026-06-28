<?php

/**
 * Plugin Name:       SupportBay
 * Plugin URI:        https://supportbay.io
 * Description:       Modern WordPress support system with ticketing, Envato verification, live chat, AI chatbot, and provider integrations.
 * Version:           0.2.0
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


/**
 * DEV ONLY: Flow Test
 */
if (defined('WP_DEBUG') && WP_DEBUG) {

  add_action('init', function () {

    if (! isset($_GET['sbay_test'])) {
      return;
    }

    $container = \SupportBay\Core\Application::container();

    $test = sanitize_key((string) $_GET['sbay_test']);

    switch ($test) {

      case 'attachment':
        \SupportBay\Dev\AttachmentFlowTest::run(
          $container->make(\SupportBay\Modules\Tickets\Services\TicketService::class),
          $container->make(\SupportBay\Modules\Messages\Services\MessageService::class),
          $container->make(\SupportBay\Modules\Attachments\Services\AttachmentService::class),
          $container->make(\SupportBay\Modules\Activities\Services\ActivityService::class),
        );
        break;

      case 'activity':
        \SupportBay\Dev\ActivityFlowTest::run(
          $container->make(\SupportBay\Modules\Tickets\Services\TicketService::class),
          $container->make(\SupportBay\Modules\Messages\Services\MessageService::class),
          $container->make(\SupportBay\Modules\Activities\Services\ActivityService::class),
        );
        break;

      case 'message':
        \SupportBay\Dev\MessageFlowTest::run(
          $container->make(\SupportBay\Modules\Tickets\Services\TicketService::class),
          $container->make(\SupportBay\Modules\Messages\Services\MessageService::class),
        );
        break;

      case 'ticket':
        \SupportBay\Dev\TicketFlowTest::run(
          $container->get(\SupportBay\Modules\Tickets\Services\TicketService::class),
        );
        break;

      case 'department':
        \SupportBay\Dev\DepartmentFlowTest::run(
          $container->get(\SupportBay\Modules\Departments\Services\DepartmentService::class),
        );
        break;

      default:
        echo '<pre>';
        echo "Unknown SupportBay test: {$test}\n\n";
        echo "Available tests:\n";
        echo "- attachment\n";
        echo "- activity\n";
        echo "- message\n";
        echo '</pre>';
    }

    exit;
  });
}
