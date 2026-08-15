<?php

declare(strict_types=1);

namespace SupportBay\Dev;

use SupportBay\Core\Testing\Assert;
use SupportBay\Core\Testing\FlowTest;
use SupportBay\Modules\Admin\AdminPage;

final class ReactAdminFlowTest extends FlowTest {
  protected static function title(): string {
    return 'React Admin Flow Test';
  }

  protected static function execute(...$services): void {
    /** @var AdminPage $adminPage */
    [$adminPage] = $services;

    echo "🚀 Starting SupportBay React Admin Flow Test...\n\n";

    wp_set_current_user(1);

    Assert::true(
      has_action('admin_menu', [$adminPage, 'registerMenu']) !== false,
      'SupportBay administrator menu hook is registered.'
    );

    $adminPage->enqueueAssets('toplevel_page_supportbay');
    $adminPage->enqueueAssets('supportbay_page_supportbay-reports');
    $adminPage->enqueueAssets('supportbay_page_supportbay-settings');

    Assert::true(
      wp_style_is('supportbay-admin', 'enqueued'),
      'Compiled administrator stylesheet is enqueued.'
    );
    Assert::true(
      wp_script_is('supportbay-admin', 'enqueued'),
      'Compiled React administrator application is enqueued.'
    );

    $bootstrap = wp_scripts()->get_data('supportbay-admin', 'before');

    Assert::true(
      is_array($bootstrap)
      && str_contains(implode('', $bootstrap), 'restNonce')
      && str_contains(implode('', $bootstrap), 'adminUrl')
      && str_contains(implode('', $bootstrap), 'tickets')
      && str_contains(implode('', $bootstrap), 'reports')
      && str_contains(implode('', $bootstrap), 'settings'),
      'Each administrator page receives authenticated API configuration and its active section.'
    );

    ob_start();
    $adminPage->render();
    $markup = (string) ob_get_clean();

    Assert::true(
      str_contains($markup, 'supportbay-admin-app')
      && str_contains($markup, 'Support Tickets')
      && str_contains($markup, 'Reports')
      && str_contains($markup, 'Settings'),
      'Administrator page renders the shared PHP navigation and React mount point.'
    );

    $providerWorkspace = file_get_contents(
      dirname(__DIR__, 2) . '/assets/src/admin/ProviderWorkspace.tsx'
    );

    Assert::true(
      is_string($providerWorkspace)
      && str_contains($providerWorkspace, "adminGet<ProviderItem[]>('providers')")
      && str_contains($providerWorkspace, 'providers/${provider.id}/${action}')
      && str_contains($providerWorkspace, 'providers/${editing.id}/configuration')
      && str_contains($providerWorkspace, 'providers/${provider.id}/test-connection')
      && str_contains($providerWorkspace, 'provider.connection_test_available')
      && str_contains($providerWorkspace, "field.type === 'secret' ? 'password'")
      && ! str_contains($providerWorkspace, 'client_secret'),
      'Settings includes schema-driven, secret-safe provider configuration and lifecycle controls.'
    );

    $settingsWorkspace = file_get_contents(
      dirname(__DIR__, 2) . '/assets/src/admin/SettingsWorkspace.tsx'
    );
    $templateWorkspace = file_get_contents(
      dirname(__DIR__, 2) . '/assets/src/admin/NotificationTemplateWorkspace.tsx'
    );
    $logWorkspace = file_get_contents(
      dirname(__DIR__, 2) . '/assets/src/admin/NotificationLogWorkspace.tsx'
    );
    $reportWorkspace = file_get_contents(
      dirname(__DIR__, 2) . '/assets/src/admin/NotificationReportWorkspace.tsx'
    );
    $ticketConversation = file_get_contents(
      dirname(__DIR__, 2) . '/assets/src/shared/tickets/TicketConversation.tsx'
    );

    Assert::true(
      is_string($reportWorkspace)
      && str_contains($reportWorkspace, 'reports/notifications?${query}')
      && str_contains($reportWorkspace, 'Daily delivery trend')
      && str_contains($reportWorkspace, 'By event')
      && str_contains($reportWorkspace, 'By channel')
      && ! str_contains($reportWorkspace, 'recipient'),
      'Reports renders server-derived notification metrics without recipient data.'
    );

    Assert::true(
      is_string($ticketConversation)
      && str_contains($ticketConversation, "transition('resolve')")
      && str_contains($ticketConversation, 'Resolve ticket')
      && str_contains($ticketConversation, "['resolved','closed'].includes(ticket.status)"),
      'Agent ticket detail supports resolution and blocks final-state composers.'
    );

    Assert::true(
      is_string($settingsWorkspace)
      && str_contains($settingsWorkspace, 'Email Notifications')
      && str_contains($settingsWorkspace, 'Delivery Logs')
      && str_contains($settingsWorkspace, 'Integrations')
      && str_contains($settingsWorkspace, '<NotificationTemplateWorkspace />')
      && str_contains($settingsWorkspace, '<NotificationLogWorkspace />')
      && str_contains($settingsWorkspace, '<ProviderWorkspace />'),
      'Settings provides shared navigation for notification templates and integrations.'
    );

    Assert::true(
      is_string($logWorkspace)
      && str_contains($logWorkspace, 'admin/notifications?${query}')
      && str_contains($logWorkspace, 'admin/notifications/${id}')
      && str_contains($logWorkspace, 'admin/notifications/${log.id}/retry')
      && str_contains($logWorkspace, "adminGet<NotificationRetention>('admin/notification-retention')")
      && str_contains($logWorkspace, "adminPut<NotificationRetention>('admin/notification-retention', retention)")
      && str_contains($logWorkspace, "admin/notification-retention/cleanup")
      && str_contains($logWorkspace, 'Active and retry-eligible deliveries are always preserved')
      && str_contains($logWorkspace, 'Notification log pagination')
      && str_contains($logWorkspace, 'selected.can_retry')
      && ! str_contains($logWorkspace, 'raw_metadata')
      && ! str_contains($logWorkspace, 'headers')
      && ! str_contains($logWorkspace, 'content:'),
      'Delivery Logs uses protected safe diagnostics and retry eligibility without sensitive payload fields.'
    );

    Assert::true(
      is_string($templateWorkspace)
      && str_contains($templateWorkspace, "adminGet<NotificationTemplate[]>('admin/notification-templates')")
      && str_contains($templateWorkspace, "adminGet<NotificationPreferences>('admin/notification-preferences')")
      && str_contains($templateWorkspace, "adminPut<NotificationPreferences>('admin/notification-preferences', preferences)")
      && str_contains($templateWorkspace, 'Email Preferences')
      && str_contains($templateWorkspace, 'Save preferences')
      && str_contains($templateWorkspace, 'adminPut<NotificationTemplate>(path, payload)')
      && str_contains($templateWorkspace, '`${path}/preview`')
      && str_contains($templateWorkspace, '`${path}/test-email`')
      && str_contains($templateWorkspace, '`${path}/reset`')
      && str_contains($templateWorkspace, 'dangerouslySetInnerHTML={{ __html: preview.html_content }}')
      && ! str_contains(strtolower($templateWorkspace), 'smtp'),
      'Email Notifications uses protected template, preview, reset, and WordPress test-email APIs without SMTP configuration.'
    );

    $adminBundle = file_get_contents(
      dirname(__DIR__, 2) . '/assets/dist/supportbay-admin.js'
    );

    Assert::true(
      is_string($adminBundle)
      && str_contains($adminBundle, 'Notification Templates')
      && str_contains($adminBundle, '/preview')
      && str_contains($adminBundle, '/test-email')
      && str_contains($adminBundle, 'notification-preferences')
      && str_contains($adminBundle, 'Email Preferences')
      && str_contains($adminBundle, 'Send test email')
      && str_contains($adminBundle, 'Delivery Logs')
      && str_contains($adminBundle, 'Retry delivery')
      && str_contains($adminBundle, 'Log retention')
      && str_contains($adminBundle, 'Run cleanup now')
      && str_contains($adminBundle, 'Delivery Report')
      && str_contains($adminBundle, 'Daily delivery trend'),
      'Compiled administrator bundle contains the notification template workspace.'
    );

    $verificationDirectory = file_get_contents(
      dirname(__DIR__, 2) . '/assets/src/admin/VerificationDirectory.tsx'
    );

    Assert::true(
      is_string($verificationDirectory)
      && str_contains($verificationDirectory, 'verifications?${query}')
      && str_contains($verificationDirectory, 'All Providers')
      && str_contains($verificationDirectory, 'All Statuses')
      && str_contains($verificationDirectory, 'Verification pagination'),
      'Support Tickets includes the server-backed Verification Directory workspace.'
    );
  }
}
