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
if (
  defined('WP_DEBUG') && WP_DEBUG
  && defined('SBAY_ENABLE_FLOW_TESTS') && SBAY_ENABLE_FLOW_TESTS
  && in_array(wp_get_environment_type(), ['local', 'development'], true)
) {

  add_action('init', function () {

    if (! isset($_GET['sbay_test'])) {
      return;
    }

    $nonce = isset($_GET['sbay_test_nonce'])
      ? sanitize_text_field(wp_unslash((string) $_GET['sbay_test_nonce']))
      : '';
    $isCli = PHP_SAPI === 'cli';
    if (! $isCli && (! is_user_logged_in() || ! current_user_can('manage_options') || ! wp_verify_nonce($nonce, 'sbay_run_flow_test'))) {
      wp_die(
        esc_html__('SupportBay flow tests require an authenticated administrator and a valid test nonce.', 'supportbay'),
        esc_html__('SupportBay flow test denied', 'supportbay'),
        ['response' => 403],
      );
    }

    $container = \SupportBay\Core\Application::container();

    if ($isCli && ! function_exists('wp_delete_user')) {
      require_once ABSPATH . 'wp-admin/includes/user.php';
    }
    if ($isCli && ! function_exists('wp_tempnam')) {
      require_once ABSPATH . 'wp-admin/includes/file.php';
    }

    $test = sanitize_key((string) $_GET['sbay_test']);

    switch ($test) {

      case 'ticket':
        \SupportBay\Dev\TicketFlowTest::run(
          $container->get(\SupportBay\Modules\Tickets\Services\TicketService::class),
        );
        break;

      case 'security-authorization':
        \SupportBay\Dev\SecurityAuthorizationFlowTest::run(
          $container->get(\SupportBay\Modules\Tickets\Services\TicketService::class),
          $container->get(\SupportBay\Modules\Tickets\Services\TicketAccessPolicy::class),
        );
        break;

      case 'installation-lifecycle':
        \SupportBay\Dev\InstallationLifecycleFlowTest::run();
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

      case 'saved-reply':
        \SupportBay\Dev\SavedReplyFlowTest::run(
          $container->get(\SupportBay\Modules\SavedReplies\Services\SavedReplyService::class),
        );
        break;

      case 'category':
        \SupportBay\Dev\CategoryFlowTest::run(
          $container->get(\SupportBay\Modules\Categories\Services\CategoryService::class),
        );
        break;

      case 'assign-rule':
        \SupportBay\Dev\AssignRuleFlowTest::run(
          $container->get(\SupportBay\Modules\AssignRules\Services\AssignRuleService::class),
          $container->get(\SupportBay\Modules\Categories\Services\CategoryService::class),
          $container->get(\SupportBay\Modules\AssignRules\Http\Controllers\AssignRuleController::class),
        );
        break;

      case 'tag':
        \SupportBay\Dev\TagFlowTest::run(
          $container->get(\SupportBay\Modules\Tags\Services\TagService::class),
          $container->get(\SupportBay\Modules\Tickets\Services\TicketService::class),
          $container->get(\SupportBay\Modules\Tags\Http\Controllers\TagController::class),
          $container->get(\SupportBay\Modules\Activities\Services\ActivityService::class),
        );
        break;

      case 'custom-field':
        \SupportBay\Dev\CustomFieldFlowTest::run(
          $container->get(\SupportBay\Modules\CustomFields\Services\CustomFieldService::class),
          $container->get(\SupportBay\Modules\Tickets\Services\TicketService::class),
          $container->get(\SupportBay\Modules\CustomFields\Http\Controllers\CustomFieldController::class),
          $container->get(\SupportBay\Modules\Activities\Services\ActivityService::class),
        );
        break;

      case 'role':
        \SupportBay\Dev\RoleFlowTest::run(
          $container->get(\SupportBay\Modules\Roles\Services\SupportRoleService::class),
          $container->get(\SupportBay\Modules\Roles\Http\Controllers\SupportRoleController::class),
        );
        break;

      case 'customer-auth':
        \SupportBay\Dev\CustomerAuthenticationFlowTest::run(
          $container->get(\SupportBay\Modules\Auth\Http\CustomerAuthController::class),
          $container->get(\SupportBay\Modules\Customers\Services\CustomerService::class),
          $container->get(\SupportBay\Modules\Settings\Services\GeneralSettingsService::class),
        );
        break;

      case 'guest-ticket':
        \SupportBay\Dev\GuestTicketFlowTest::run(
          $container->get(\SupportBay\Modules\Portal\Services\PortalService::class),
          $container->get(\SupportBay\Modules\Customers\Services\CustomerService::class),
          $container->get(\SupportBay\Modules\Tickets\Services\TicketService::class),
          $container->get(\SupportBay\Modules\Messages\Services\MessageService::class),
          $container->get(\SupportBay\Modules\Settings\Services\GeneralSettingsService::class),
        );
        break;

      case 'general-settings':
        \SupportBay\Dev\GeneralSettingsFlowTest::run(
          $container->get(\SupportBay\Modules\Settings\Services\GeneralSettingsService::class),
        );
        break;

      case 'weekend-holiday':
        \SupportBay\Dev\WeekendHolidayFlowTest::run(
          $container->get(\SupportBay\Modules\Settings\Services\WeekendHolidaySettingsService::class),
        );
        break;

      case 'auto-close':
        \SupportBay\Dev\AutoCloseFlowTest::run(
          $container->get(\SupportBay\Modules\Tickets\Services\TicketService::class),
          $container->get(\SupportBay\Modules\Tickets\Repositories\TicketRepository::class),
          $container->get(\SupportBay\Modules\Tickets\Services\TicketLifecycleWorker::class),
          $container->get(\SupportBay\Modules\Settings\Services\AutoCloseSettingsService::class),
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
            \SupportBay\Modules\Categories\Services\CategoryService::class
          ),
          $container->get(
            \SupportBay\Modules\Attachments\Services\AttachmentService::class
          ),
          $container->get(
            \SupportBay\Core\Integrations\IntegrationManager::class
          ),
          $container->get(
            \SupportBay\Modules\Providers\Services\ProviderService::class
          ),
          $container->get(
            \SupportBay\Modules\CustomFields\Services\CustomFieldService::class
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
          $container->get(\SupportBay\Modules\Activities\Services\ActivityService::class),
          $container->get(\SupportBay\Modules\AssignRules\Services\AssignRuleService::class),
        );
        break;

      case 'migration':
        \SupportBay\Dev\DatabaseMigrationFlowTest::run();
        break;

      case 'notification-retry':
        \SupportBay\Dev\NotificationRetryFlowTest::run(
          $container->get(\SupportBay\Modules\Notifications\Services\NotificationRetryWorker::class),
          $container->get(\SupportBay\Modules\Notifications\Services\NotificationService::class),
          $container->get(\SupportBay\Modules\Notifications\Repositories\NotificationLogRepository::class),
        );
        break;

      case 'notification-template':
        \SupportBay\Dev\NotificationTemplateFlowTest::run(
          $container->get(\SupportBay\Modules\Notifications\Services\NotificationTemplateService::class),
        );
        break;

      case 'notification-template-api':
        \SupportBay\Dev\NotificationTemplateApiFlowTest::run(
          $container->get(\SupportBay\Modules\Notifications\Http\Controllers\NotificationTemplateController::class),
          $container->get(\SupportBay\Modules\Notifications\Repositories\NotificationLogRepository::class),
        );
        break;

      case 'notification-preference':
        \SupportBay\Dev\NotificationPreferenceFlowTest::run(
          $container->get(\SupportBay\Modules\Notifications\Services\NotificationPreferenceService::class),
        );
        break;

      case 'notification-preference-api':
        \SupportBay\Dev\NotificationPreferenceApiFlowTest::run(
          $container->get(\SupportBay\Modules\Notifications\Http\Controllers\NotificationPreferenceController::class),
        );
        break;

      case 'notification-retention':
        \SupportBay\Dev\NotificationRetentionFlowTest::run(
          $container->get(\SupportBay\Modules\Notifications\Services\NotificationRetentionService::class),
          $container->get(\SupportBay\Modules\Notifications\Repositories\NotificationLogRepository::class),
        );
        break;

      case 'ticket-metrics':
        \SupportBay\Dev\TicketMetricFlowTest::run(
          $container->get(\SupportBay\Modules\Tickets\Services\TicketMetricService::class),
          $container->get(\SupportBay\Modules\Tickets\Http\Controllers\TicketMetricController::class),
          $container->get(\SupportBay\Modules\Tickets\Repositories\TicketRepository::class),
          $container->get(\SupportBay\Modules\Messages\Repositories\MessageRepository::class),
          $container->get(\SupportBay\Common\Utilities\CsvExporter::class),
          $container->get(\SupportBay\Modules\Categories\Services\CategoryService::class),
          $container->get(\SupportBay\Modules\Tags\Services\TagService::class),
          $container->get(\SupportBay\Modules\CustomFields\Services\CustomFieldService::class),
        );
        break;

      case 'api-webhook':
        \SupportBay\Dev\ApiWebhookFlowTest::run(
          $container->get(\SupportBay\Modules\Tickets\Http\Controllers\TicketController::class),
          $container->get(\SupportBay\Modules\Tickets\Services\TicketService::class),
          $container->get(\SupportBay\Modules\Messages\Services\MessageService::class),
          $container->get(\SupportBay\Modules\Customers\Services\CustomerService::class),
          $container->get(\SupportBay\Modules\Departments\Services\DepartmentService::class),
          $container->get(\SupportBay\Modules\Categories\Services\CategoryService::class),
          $container->get(\SupportBay\Modules\Providers\Services\ProviderService::class),
          $container->get(\SupportBay\Modules\Verifications\Services\VerificationService::class),
          $container->get(\SupportBay\Core\Integrations\IntegrationManager::class),
          $container->get(\SupportBay\Modules\CustomFields\Services\CustomFieldService::class),
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

        \SupportBay\Dev\SavedReplyFlowTest::run(
          $container->get(\SupportBay\Modules\SavedReplies\Services\SavedReplyService::class),
        );

        \SupportBay\Dev\CategoryFlowTest::run(
          $container->get(\SupportBay\Modules\Categories\Services\CategoryService::class),
        );

        \SupportBay\Dev\AssignRuleFlowTest::run(
          $container->get(\SupportBay\Modules\AssignRules\Services\AssignRuleService::class),
          $container->get(\SupportBay\Modules\Categories\Services\CategoryService::class),
          $container->get(\SupportBay\Modules\AssignRules\Http\Controllers\AssignRuleController::class),
        );

        \SupportBay\Dev\TagFlowTest::run(
          $container->get(\SupportBay\Modules\Tags\Services\TagService::class),
          $container->get(\SupportBay\Modules\Tickets\Services\TicketService::class),
          $container->get(\SupportBay\Modules\Tags\Http\Controllers\TagController::class),
          $container->get(\SupportBay\Modules\Activities\Services\ActivityService::class),
        );

        \SupportBay\Dev\CustomFieldFlowTest::run(
          $container->get(\SupportBay\Modules\CustomFields\Services\CustomFieldService::class),
          $container->get(\SupportBay\Modules\Tickets\Services\TicketService::class),
          $container->get(\SupportBay\Modules\CustomFields\Http\Controllers\CustomFieldController::class),
          $container->get(\SupportBay\Modules\Activities\Services\ActivityService::class),
        );

        \SupportBay\Dev\RoleFlowTest::run(
          $container->get(\SupportBay\Modules\Roles\Services\SupportRoleService::class),
          $container->get(\SupportBay\Modules\Roles\Http\Controllers\SupportRoleController::class),
        );

        \SupportBay\Dev\CustomerAuthenticationFlowTest::run(
          $container->get(\SupportBay\Modules\Auth\Http\CustomerAuthController::class),
          $container->get(\SupportBay\Modules\Customers\Services\CustomerService::class),
          $container->get(\SupportBay\Modules\Settings\Services\GeneralSettingsService::class),
        );

        \SupportBay\Dev\CustomerFlowTest::run(
          $container->get(\SupportBay\Modules\Customers\Services\CustomerService::class)
        );

        \SupportBay\Dev\GuestTicketFlowTest::run(
          $container->get(\SupportBay\Modules\Portal\Services\PortalService::class),
          $container->get(\SupportBay\Modules\Customers\Services\CustomerService::class),
          $container->get(\SupportBay\Modules\Tickets\Services\TicketService::class),
          $container->get(\SupportBay\Modules\Messages\Services\MessageService::class),
          $container->get(\SupportBay\Modules\Settings\Services\GeneralSettingsService::class),
        );

        \SupportBay\Dev\GeneralSettingsFlowTest::run(
          $container->get(\SupportBay\Modules\Settings\Services\GeneralSettingsService::class),
        );

        \SupportBay\Dev\WeekendHolidayFlowTest::run(
          $container->get(\SupportBay\Modules\Settings\Services\WeekendHolidaySettingsService::class),
        );

        \SupportBay\Dev\AutoCloseFlowTest::run(
          $container->get(\SupportBay\Modules\Tickets\Services\TicketService::class),
          $container->get(\SupportBay\Modules\Tickets\Repositories\TicketRepository::class),
          $container->get(\SupportBay\Modules\Tickets\Services\TicketLifecycleWorker::class),
          $container->get(\SupportBay\Modules\Settings\Services\AutoCloseSettingsService::class),
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
          $container->get(\SupportBay\Modules\Providers\Services\ProviderConnectionService::class),
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
            \SupportBay\Modules\Categories\Services\CategoryService::class
          ),
          $container->get(
            \SupportBay\Modules\Attachments\Services\AttachmentService::class
          ),
          $container->get(
            \SupportBay\Core\Integrations\IntegrationManager::class
          ),
          $container->get(
            \SupportBay\Modules\Providers\Services\ProviderService::class
          ),
          $container->get(
            \SupportBay\Modules\CustomFields\Services\CustomFieldService::class
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
          $container->get(\SupportBay\Modules\Activities\Services\ActivityService::class),
          $container->get(\SupportBay\Modules\AssignRules\Services\AssignRuleService::class),
        );

        \SupportBay\Dev\DatabaseMigrationFlowTest::run();

        \SupportBay\Dev\NotificationRetryFlowTest::run(
          $container->get(\SupportBay\Modules\Notifications\Services\NotificationRetryWorker::class),
          $container->get(\SupportBay\Modules\Notifications\Services\NotificationService::class),
          $container->get(\SupportBay\Modules\Notifications\Repositories\NotificationLogRepository::class),
        );

        \SupportBay\Dev\NotificationTemplateFlowTest::run(
          $container->get(\SupportBay\Modules\Notifications\Services\NotificationTemplateService::class),
        );

        \SupportBay\Dev\NotificationTemplateApiFlowTest::run(
          $container->get(\SupportBay\Modules\Notifications\Http\Controllers\NotificationTemplateController::class),
          $container->get(\SupportBay\Modules\Notifications\Repositories\NotificationLogRepository::class),
        );

        \SupportBay\Dev\NotificationPreferenceFlowTest::run(
          $container->get(\SupportBay\Modules\Notifications\Services\NotificationPreferenceService::class),
        );

        \SupportBay\Dev\NotificationPreferenceApiFlowTest::run(
          $container->get(\SupportBay\Modules\Notifications\Http\Controllers\NotificationPreferenceController::class),
        );

        \SupportBay\Dev\TicketMetricFlowTest::run(
          $container->get(\SupportBay\Modules\Tickets\Services\TicketMetricService::class),
          $container->get(\SupportBay\Modules\Tickets\Http\Controllers\TicketMetricController::class),
          $container->get(\SupportBay\Modules\Tickets\Repositories\TicketRepository::class),
          $container->get(\SupportBay\Modules\Messages\Repositories\MessageRepository::class),
          $container->get(\SupportBay\Common\Utilities\CsvExporter::class),
          $container->get(\SupportBay\Modules\Categories\Services\CategoryService::class),
          $container->get(\SupportBay\Modules\Tags\Services\TagService::class),
          $container->get(\SupportBay\Modules\CustomFields\Services\CustomFieldService::class),
        );

        \SupportBay\Dev\ApiWebhookFlowTest::run(
          $container->get(\SupportBay\Modules\Tickets\Http\Controllers\TicketController::class),
          $container->get(\SupportBay\Modules\Tickets\Services\TicketService::class),
          $container->get(\SupportBay\Modules\Messages\Services\MessageService::class),
          $container->get(\SupportBay\Modules\Customers\Services\CustomerService::class),
          $container->get(\SupportBay\Modules\Departments\Services\DepartmentService::class),
          $container->get(\SupportBay\Modules\Categories\Services\CategoryService::class),
          $container->get(\SupportBay\Modules\Providers\Services\ProviderService::class),
          $container->get(\SupportBay\Modules\Verifications\Services\VerificationService::class),
          $container->get(\SupportBay\Core\Integrations\IntegrationManager::class),
          $container->get(\SupportBay\Modules\CustomFields\Services\CustomFieldService::class),
        );

        \SupportBay\Dev\ReactAdminFlowTest::run(
          $container->get(\SupportBay\Modules\Admin\AdminPage::class),
        );

        \SupportBay\Dev\SecurityAuthorizationFlowTest::run(
          $container->get(\SupportBay\Modules\Tickets\Services\TicketService::class),
          $container->get(\SupportBay\Modules\Tickets\Services\TicketAccessPolicy::class),
        );

        \SupportBay\Dev\InstallationLifecycleFlowTest::run();
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
        echo "- weekend-holiday\n";
        echo "- auto-close\n";
        echo "- assign-rule\n";
        echo "- provider\n";
        echo "- verification\n";
        echo "- provider-verification\n";
        echo "- ticket-verification\n";
        echo "- oauth\n";
        echo "- portal-api\n";
        echo "- portal-react\n";
        echo "- notification\n";
        echo "- migration\n";
        echo "- notification-api\n";
        echo "- notification-retry\n";
        echo "- notification-template\n";
        echo "- notification-template-api\n";
        echo "- api-webhook\n";
        echo "- admin-react\n";
        echo "- all\n";
        echo '</pre>';
    }

    exit($isCli && \SupportBay\Core\Testing\FlowTest::failureCount() > 0 ? 1 : 0);
  });
}
