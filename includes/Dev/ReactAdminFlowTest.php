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
      && str_contains(implode('', $bootstrap), 'settings')
      && str_contains(implode('', $bootstrap), 'canExportReports')
      && str_contains(implode('', $bootstrap), 'canManageSavedReplies')
      && str_contains(implode('', $bootstrap), 'canManageCategories'),
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
    $ticketReportWorkspace = file_get_contents(
      dirname(__DIR__, 2) . '/assets/src/admin/TicketReportWorkspace.tsx'
    );
    $reportsWorkspace = file_get_contents(
      dirname(__DIR__, 2) . '/assets/src/admin/ReportsWorkspace.tsx'
    );
    $slaWorkspace = file_get_contents(
      dirname(__DIR__, 2) . '/assets/src/admin/TicketSlaWorkspace.tsx'
    );
    $ticketConversation = file_get_contents(
      dirname(__DIR__, 2) . '/assets/src/shared/tickets/TicketConversation.tsx'
    );
    $savedReplyWorkspace = file_get_contents(
      dirname(__DIR__, 2) . '/assets/src/admin/SavedReplyWorkspace.tsx'
    );
    $savedReplyPicker = file_get_contents(
      dirname(__DIR__, 2) . '/assets/src/shared/tickets/SavedReplyPicker.tsx'
    );
    $savedReplyRenderer = file_get_contents(
      dirname(__DIR__, 2) . '/assets/src/shared/tickets/savedReplyRenderer.ts'
    );
    $categoryWorkspace = file_get_contents(
      dirname(__DIR__, 2) . '/assets/src/admin/CategoryWorkspace.tsx'
    );

    Assert::true(
      is_string($reportsWorkspace)
      && str_contains($reportsWorkspace, 'Ticket Performance')
      && str_contains($reportsWorkspace, 'Notification Delivery')
      && is_string($ticketReportWorkspace)
      && str_contains($ticketReportWorkspace, 'reports/tickets?${query}')
      && str_contains($ticketReportWorkspace, 'reports/tickets/export?${query()}')
      && str_contains($ticketReportWorkspace, 'admin/tickets/options')
      && str_contains($ticketReportWorkspace, 'Daily support activity')
      && str_contains($ticketReportWorkspace, 'By department')
      && str_contains($ticketReportWorkspace, 'By category')
      && str_contains($ticketReportWorkspace, 'By agent')
      && str_contains($ticketReportWorkspace, 'category_id')
      && str_contains($ticketReportWorkspace, 'Uncategorized')
      && str_contains($ticketReportWorkspace, 'First-response SLA')
      && str_contains($ticketReportWorkspace, 'response_bands')
      && is_string($slaWorkspace)
      && str_contains($slaWorkspace, 'admin/ticket-sla-policy')
      && str_contains($slaWorkspace, 'Save SLA policy'),
      'Reports combines ticket performance and notification delivery workspaces.'
    );

    Assert::true(
      is_string($reportWorkspace)
      && str_contains($reportWorkspace, 'reports/notifications?${query}')
      && str_contains($reportWorkspace, 'reports/notifications/export?${query()}')
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
      && str_contains($ticketConversation, "['resolved','closed'].includes(ticket.status)")
      && str_contains($ticketConversation, '<SavedReplyPicker')
      && str_contains($ticketConversation, 'loadSavedReplies')
      && is_string($savedReplyPicker)
      && str_contains($savedReplyPicker, 'Search saved replies')
      && str_contains($savedReplyPicker, 'All Categories')
      && str_contains($savedReplyPicker, 'Replace the current draft')
      && str_contains($savedReplyPicker, 'await track(reply.id)')
      && str_contains($savedReplyPicker, 'select(reply)')
      && is_string($savedReplyRenderer)
      && str_contains($savedReplyRenderer, 'escapeHtml')
      && str_contains($savedReplyRenderer, 'hasOwnProperty.call')
      && str_contains($ticketConversation, 'renderSavedReply(saved.content,savedReplyContext)')
      && str_contains($ticketConversation, 'loadSavedReplies(ticket.department_id)'),
      'Agent ticket detail supports resolution, final-state enforcement, and guarded saved-reply insertion.'
    );

    $ticketWorkspace = file_get_contents(
      dirname(__DIR__, 2) . '/assets/src/shared/tickets/TicketWorkspace.tsx'
    );
    Assert::true(
      is_string($ticketWorkspace)
      && str_contains($ticketWorkspace, 'sla_state: query.slaState')
      && str_contains($ticketWorkspace, 'SLA Due First')
      && str_contains($ticketWorkspace, 'SLA Breached')
      && str_contains($ticketWorkspace, 'mode===\'staff\'&&slaLabel(ticket)'),
      'Shared ticket workspace renders server-owned SLA controls only for staff.',
    );

    Assert::true(
      is_string($settingsWorkspace)
      && str_contains($settingsWorkspace, 'Email Notifications')
      && str_contains($settingsWorkspace, 'Delivery Logs')
      && str_contains($settingsWorkspace, 'Saved Replies')
      && str_contains($settingsWorkspace, 'Categories')
      && str_contains($settingsWorkspace, 'Ticket SLA')
      && str_contains($settingsWorkspace, 'Integrations')
      && str_contains($settingsWorkspace, '<NotificationTemplateWorkspace />')
      && str_contains($settingsWorkspace, '<NotificationLogWorkspace />')
      && str_contains($settingsWorkspace, '<SavedReplyWorkspace />')
      && str_contains($settingsWorkspace, '<CategoryWorkspace />')
      && str_contains($settingsWorkspace, '<ProviderWorkspace />'),
      'Settings provides shared navigation for notification templates and integrations.'
    );

    Assert::true(
      is_string($categoryWorkspace)
      && str_contains($categoryWorkspace, "adminGet<Category[]>('categories')")
      && str_contains($categoryWorkspace, "adminPost<Category>('categories'")
      && str_contains($categoryWorkspace, 'adminPut<Category>(`categories/${selected.id}`')
      && str_contains($categoryWorkspace, 'adminDelete(`categories/${selected.id}`')
      && str_contains($categoryWorkspace, 'Global — all departments')
      && str_contains($categoryWorkspace, 'Create Category')
      && str_contains($categoryWorkspace, 'Save Changes')
      && str_contains($categoryWorkspace, 'Categories used by tickets cannot be deleted'),
      'Settings provides capability-gated category lifecycle management and safe deletion guidance.'
    );

    Assert::true(
      is_string($savedReplyWorkspace)
      && str_contains($savedReplyWorkspace, "saved-replies?status=active")
      && str_contains($savedReplyWorkspace, "saved-replies?status=inactive")
      && str_contains($savedReplyWorkspace, '<RichTextEditor')
      && str_contains($savedReplyWorkspace, 'Create Saved Reply')
      && str_contains($savedReplyWorkspace, 'Save Changes')
      && str_contains($savedReplyWorkspace, 'adminDelete')
      && str_contains($savedReplyWorkspace, 'This cannot be undone')
      && str_contains($savedReplyWorkspace, 'composer insertions')
      && str_contains($savedReplyWorkspace, 'last_used_at')
      && str_contains($savedReplyWorkspace, 'Most Used')
      && str_contains($savedReplyWorkspace, 'Recently Used')
      && str_contains($savedReplyWorkspace, 'All Categories')
      && str_contains($savedReplyWorkspace, 'For example: Billing')
      && str_contains($savedReplyWorkspace, 'active.meta.placeholders')
      && str_contains($savedReplyWorkspace, 'Saved reply placeholders')
      && str_contains($savedReplyWorkspace, 'Global — all departments')
      && str_contains($savedReplyWorkspace, "adminGet<Department[]>('departments?status=active')"),
      'Settings provides capability-gated saved-reply lifecycle management with rich-text editing and confirmed deletion.'
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
      && str_contains($adminBundle, 'Daily delivery trend')
      && str_contains($adminBundle, 'Support Tickets Report')
      && str_contains($adminBundle, 'Daily support activity')
      && str_contains($adminBundle, 'Export CSV')
      && str_contains($adminBundle, 'First-response SLA')
      && str_contains($adminBundle, 'Save SLA policy'),
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
