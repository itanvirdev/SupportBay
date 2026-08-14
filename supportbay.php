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

      case 'ticket':
        \SupportBay\Dev\TicketFlowTest::run(
          $container->get(\SupportBay\Modules\Tickets\Services\TicketService::class),
        );
        break;

      case 'message':
        \SupportBay\Dev\MessageFlowTest::run(
          $container->make(\SupportBay\Modules\Tickets\Services\TicketService::class),
          $container->make(\SupportBay\Modules\Messages\Services\MessageService::class),
        );
        break;

      case 'activity':
        \SupportBay\Dev\ActivityFlowTest::run(
          $container->make(\SupportBay\Modules\Tickets\Services\TicketService::class),
          $container->make(\SupportBay\Modules\Messages\Services\MessageService::class),
          $container->make(\SupportBay\Modules\Activities\Services\ActivityService::class),
        );
        break;

      case 'attachment':
        \SupportBay\Dev\AttachmentFlowTest::run(
          $container->make(\SupportBay\Modules\Tickets\Services\TicketService::class),
          $container->make(\SupportBay\Modules\Messages\Services\MessageService::class),
          $container->make(\SupportBay\Modules\Attachments\Services\AttachmentService::class),
          $container->make(\SupportBay\Modules\Activities\Services\ActivityService::class),
        );
        break;

      case 'department':
        \SupportBay\Dev\DepartmentFlowTest::run(
          $container->get(\SupportBay\Modules\Departments\Services\DepartmentService::class),
        );
        break;

      case 'customer':
        \SupportBay\Dev\CustomerFlowTest::run(
          $container->get(\SupportBay\Modules\Customers\Services\CustomerService::class)
        );
        break;

      case 'auth':
        \SupportBay\Dev\AuthFlowTest::run(
          $container->get(\SupportBay\Modules\Auth\Services\AuthService::class),
          $container->get(\SupportBay\Modules\Auth\Services\MagicLoginService::class)
        );
        break;

      case 'provider':
        \SupportBay\Dev\ProviderFlowTest::run(
          $container->get(\SupportBay\Modules\Providers\Services\ProviderService::class),
          $container->get(\SupportBay\Modules\Providers\Services\ProviderConfiguration::class),
          $container->get(\SupportBay\Core\Security\SecretCipher::class),
          $container->get(\SupportBay\Core\Integrations\IntegrationManager::class),
          $container->get(\SupportBay\Modules\Providers\Services\ProviderConnectionService::class),
        );
        break;

      case 'verification':
        \SupportBay\Dev\VerificationFlowTest::run(
          $container->get(
            \SupportBay\Modules\Verifications\Services\VerificationService::class
          )
        );
        break;

      case 'provider-verification':
        \SupportBay\Dev\ProviderVerificationFlowTest::run(
          $container->get(
            \SupportBay\Modules\Verifications\Services\VerificationService::class
          ),
          $container->get(
            \SupportBay\Core\Integrations\IntegrationManager::class
          )
        );
        break;

      case 'ticket-verification':
        \SupportBay\Dev\TicketVerificationFlowTest::run(
          $container->get(
            \SupportBay\Modules\Tickets\Services\TicketService::class
          ),
          $container->get(
            \SupportBay\Modules\Verifications\Services\VerificationService::class
          )
        );
        break;

      case 'oauth':
        \SupportBay\Dev\OAuthFlowTest::run(
          $container->get(
            \SupportBay\Modules\Auth\Services\OAuthLoginService::class
          ),
          $container->get(
            \SupportBay\Core\Integrations\IntegrationManager::class
          ),
          $container->get(
            \SupportBay\Modules\Customers\Services\CustomerService::class
          ),
          $container->get(
            \SupportBay\Modules\Auth\Http\OAuthRoutes::class
          )
        );
        break;

      case 'portal-api':
        \SupportBay\Dev\CustomerPortalApiFlowTest::run(
          $container->get(
            \SupportBay\Modules\Customers\Services\CustomerService::class
          ),
          $container->get(
            \SupportBay\Modules\Tickets\Services\TicketService::class
          ),
          $container->get(
            \SupportBay\Modules\Verifications\Services\VerificationService::class
          ),
          $container->get(
            \SupportBay\Modules\Messages\Services\MessageService::class
          ),
          $container->get(
            \SupportBay\Modules\Departments\Services\DepartmentService::class
          ),
          $container->get(
            \SupportBay\Modules\Attachments\Services\AttachmentService::class
          ),
          $container->get(
            \SupportBay\Core\Integrations\IntegrationManager::class
          ),
          $container->get(
            \SupportBay\Modules\Providers\Services\ProviderService::class
          )
        );
        break;

      case 'portal-react':
        \SupportBay\Dev\ReactPortalFlowTest::run(
          $container->get(
            \SupportBay\Modules\Portal\Http\PortalPage::class
          )
        );
        break;

      case 'notification':
        \SupportBay\Dev\NotificationFlowTest::run(
          $container->get(\SupportBay\Modules\Tickets\Services\TicketService::class),
          $container->get(\SupportBay\Modules\Messages\Services\MessageService::class),
          $container->get(\SupportBay\Modules\Customers\Services\CustomerService::class),
          $container->get(\SupportBay\Modules\Departments\Services\DepartmentService::class),
          $container->get(\SupportBay\Modules\Notifications\Services\NotificationService::class),
          $container->get(\SupportBay\Modules\Notifications\Repositories\NotificationLogRepository::class),
        );
        break;

      case 'migration':
        \SupportBay\Dev\DatabaseMigrationFlowTest::run();
        break;

      case 'api-webhook':
        \SupportBay\Dev\ApiWebhookFlowTest::run(
          $container->get(\SupportBay\Modules\Tickets\Http\Controllers\TicketController::class),
          $container->get(\SupportBay\Modules\Tickets\Services\TicketService::class),
          $container->get(\SupportBay\Modules\Messages\Services\MessageService::class),
          $container->get(\SupportBay\Modules\Customers\Services\CustomerService::class),
          $container->get(\SupportBay\Modules\Departments\Services\DepartmentService::class),
          $container->get(\SupportBay\Modules\Notifications\Services\NotificationService::class),
          $container->get(\SupportBay\Modules\Notifications\Repositories\NotificationLogRepository::class),
          $container->get(\SupportBay\Modules\Providers\Services\ProviderConnectionService::class),
        );
        break;

      case 'admin-react':
        \SupportBay\Dev\ReactAdminFlowTest::run(
          $container->get(\SupportBay\Modules\Admin\AdminPage::class),
        );
        break;

      case 'all':
        \SupportBay\Dev\TicketFlowTest::run(
          $container->get(\SupportBay\Modules\Tickets\Services\TicketService::class),
        );

        \SupportBay\Dev\MessageFlowTest::run(
          $container->make(\SupportBay\Modules\Tickets\Services\TicketService::class),
          $container->make(\SupportBay\Modules\Messages\Services\MessageService::class),
        );

        \SupportBay\Dev\ActivityFlowTest::run(
          $container->make(\SupportBay\Modules\Tickets\Services\TicketService::class),
          $container->make(\SupportBay\Modules\Messages\Services\MessageService::class),
          $container->make(\SupportBay\Modules\Activities\Services\ActivityService::class),
        );

        \SupportBay\Dev\AttachmentFlowTest::run(
          $container->make(\SupportBay\Modules\Tickets\Services\TicketService::class),
          $container->make(\SupportBay\Modules\Messages\Services\MessageService::class),
          $container->make(\SupportBay\Modules\Attachments\Services\AttachmentService::class),
          $container->make(\SupportBay\Modules\Activities\Services\ActivityService::class),
        );

        \SupportBay\Dev\DepartmentFlowTest::run(
          $container->get(\SupportBay\Modules\Departments\Services\DepartmentService::class),
        );

        \SupportBay\Dev\CustomerFlowTest::run(
          $container->get(\SupportBay\Modules\Customers\Services\CustomerService::class)
        );

        \SupportBay\Dev\AuthFlowTest::run(
          $container->get(\SupportBay\Modules\Auth\Services\AuthService::class),
          $container->get(\SupportBay\Modules\Auth\Services\MagicLoginService::class)
        );

        \SupportBay\Dev\ProviderFlowTest::run(
          $container->get(\SupportBay\Modules\Providers\Services\ProviderService::class),
          $container->get(\SupportBay\Modules\Providers\Services\ProviderConfiguration::class),
          $container->get(\SupportBay\Core\Security\SecretCipher::class),
          $container->get(\SupportBay\Core\Integrations\IntegrationManager::class),
        );

        \SupportBay\Dev\VerificationFlowTest::run(
          $container->get(
            \SupportBay\Modules\Verifications\Services\VerificationService::class
          )
        );

        \SupportBay\Dev\ProviderVerificationFlowTest::run(
          $container->get(
            \SupportBay\Modules\Verifications\Services\VerificationService::class
          ),
          $container->get(
            \SupportBay\Core\Integrations\IntegrationManager::class
          )
        );

        \SupportBay\Dev\TicketVerificationFlowTest::run(
          $container->get(
            \SupportBay\Modules\Tickets\Services\TicketService::class
          ),
          $container->get(
            \SupportBay\Modules\Verifications\Services\VerificationService::class
          )
        );

        \SupportBay\Dev\OAuthFlowTest::run(
          $container->get(
            \SupportBay\Modules\Auth\Services\OAuthLoginService::class
          ),
          $container->get(
            \SupportBay\Core\Integrations\IntegrationManager::class
          ),
          $container->get(
            \SupportBay\Modules\Customers\Services\CustomerService::class
          ),
          $container->get(
            \SupportBay\Modules\Auth\Http\OAuthRoutes::class
          )
        );

        \SupportBay\Dev\CustomerPortalApiFlowTest::run(
          $container->get(
            \SupportBay\Modules\Customers\Services\CustomerService::class
          ),
          $container->get(
            \SupportBay\Modules\Tickets\Services\TicketService::class
          ),
          $container->get(
            \SupportBay\Modules\Verifications\Services\VerificationService::class
          ),
          $container->get(
            \SupportBay\Modules\Messages\Services\MessageService::class
          ),
          $container->get(
            \SupportBay\Modules\Departments\Services\DepartmentService::class
          ),
          $container->get(
            \SupportBay\Modules\Attachments\Services\AttachmentService::class
          ),
          $container->get(
            \SupportBay\Core\Integrations\IntegrationManager::class
          ),
          $container->get(
            \SupportBay\Modules\Providers\Services\ProviderService::class
          )
        );

        \SupportBay\Dev\ReactPortalFlowTest::run(
          $container->get(
            \SupportBay\Modules\Portal\Http\PortalPage::class
          )
        );

        \SupportBay\Dev\NotificationFlowTest::run(
          $container->get(\SupportBay\Modules\Tickets\Services\TicketService::class),
          $container->get(\SupportBay\Modules\Messages\Services\MessageService::class),
          $container->get(\SupportBay\Modules\Customers\Services\CustomerService::class),
          $container->get(\SupportBay\Modules\Departments\Services\DepartmentService::class),
          $container->get(\SupportBay\Modules\Notifications\Services\NotificationService::class),
          $container->get(\SupportBay\Modules\Notifications\Repositories\NotificationLogRepository::class),
        );

        \SupportBay\Dev\DatabaseMigrationFlowTest::run();

        \SupportBay\Dev\ApiWebhookFlowTest::run(
          $container->get(\SupportBay\Modules\Tickets\Http\Controllers\TicketController::class),
          $container->get(\SupportBay\Modules\Tickets\Services\TicketService::class),
          $container->get(\SupportBay\Modules\Messages\Services\MessageService::class),
          $container->get(\SupportBay\Modules\Customers\Services\CustomerService::class),
          $container->get(\SupportBay\Modules\Departments\Services\DepartmentService::class),
          $container->get(\SupportBay\Modules\Providers\Services\ProviderService::class),
          $container->get(\SupportBay\Modules\Verifications\Services\VerificationService::class),
          $container->get(\SupportBay\Core\Integrations\IntegrationManager::class),
        );

        \SupportBay\Dev\ReactAdminFlowTest::run(
          $container->get(\SupportBay\Modules\Admin\AdminPage::class),
        );
        break;

      default:
        echo '<pre>';
        echo "Unknown SupportBay test: {$test}\n\n";
        echo "Available tests:\n";
        echo "- ticket\n";
        echo "- message\n";
        echo "- activity\n";
        echo "- attachment\n";
        echo "- department\n";
        echo "- customer\n";
        echo "- auth\n";
        echo "- provider\n";
        echo "- verification\n";
        echo "- provider-verification\n";
        echo "- ticket-verification\n";
        echo "- oauth\n";
        echo "- portal-api\n";
        echo "- portal-react\n";
        echo "- notification\n";
        echo "- migration\n";
        echo "- api-webhook\n";
        echo "- admin-react\n";
        echo "- all\n";
        echo '</pre>';
    }

    exit;
  });
}
