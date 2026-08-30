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
      && str_contains(implode('', $bootstrap), 'canManageCategories')
      && str_contains(implode('', $bootstrap), 'canManageTags')
      && str_contains(implode('', $bootstrap), 'canManageCustomFields')
      && str_contains(implode('', $bootstrap), 'canManageRoles')
      && str_contains(implode('', $bootstrap), 'canManageDepartments')
      && str_contains(implode('', $bootstrap), 'ticketListAutoRefreshEnabled')
      && str_contains(implode('', $bootstrap), 'ticketListAutoRefreshInterval')
      && str_contains(implode('', $bootstrap), 'attachmentPopupPreviewEnabled')
      && str_contains(implode('', $bootstrap), 'needReplyFilterEnabled'),
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
    $envatoLoginWorkspace = file_get_contents(
      dirname(__DIR__, 2) . '/assets/src/admin/EnvatoLoginWorkspace.tsx'
    );
    $envatoProvider = file_get_contents(
      dirname(__DIR__) . '/Providers/Envato/EnvatoProvider.php'
    );
    $weekendWorkspace = file_get_contents(
      dirname(__DIR__, 2) . '/assets/src/admin/WeekendHolidayWorkspace.tsx'
    );
    $autoCloseWorkspace = file_get_contents(
      dirname(__DIR__, 2) . '/assets/src/admin/AutoCloseWorkspace.tsx'
    );
    $assignRuleWorkspace = file_get_contents(
      dirname(__DIR__, 2) . '/assets/src/admin/AssignRuleWorkspace.tsx'
    );
    $adminApp = file_get_contents(
      dirname(__DIR__, 2) . '/assets/src/admin/app.tsx'
    );
    $adminPageSource = file_get_contents(
      dirname(__DIR__) . '/Modules/Admin/AdminPage.php'
    );
    $departmentWorkspace = file_get_contents(
      dirname(__DIR__, 2) . '/assets/src/admin/DepartmentWorkspace.tsx'
    );
    $generalWorkspace = file_get_contents(
      dirname(__DIR__, 2) . '/assets/src/admin/GeneralWorkspace.tsx'
    );
    $securityWorkspace = file_get_contents(
      dirname(__DIR__, 2) . '/assets/src/admin/SecurityWorkspace.tsx'
    );
    $templateWorkspace = file_get_contents(
      dirname(__DIR__, 2) . '/assets/src/admin/NotificationTemplateWorkspace.tsx'
    );
    $ticketReportWorkspace = file_get_contents(
      dirname(__DIR__, 2) . '/assets/src/admin/TicketReportWorkspace.tsx'
    );
    $reportsWorkspace = file_get_contents(
      dirname(__DIR__, 2) . '/assets/src/admin/ReportsWorkspace.tsx'
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
    $tagWorkspace = file_get_contents(
      dirname(__DIR__, 2) . '/assets/src/admin/TagWorkspace.tsx'
    );
    $customFieldWorkspace = file_get_contents(
      dirname(__DIR__, 2) . '/assets/src/admin/CustomFieldWorkspace.tsx'
    );
    $roleWorkspace = file_get_contents(
      dirname(__DIR__, 2) . '/assets/src/admin/RoleWorkspace.tsx'
    );
    $requestState = file_get_contents(
      dirname(__DIR__, 2) . '/assets/src/shared/components/RequestState.tsx'
    );
    $ticketWorkspace = file_get_contents(
      dirname(__DIR__, 2) . '/assets/src/shared/tickets/TicketWorkspace.tsx'
    );
    $customerDirectory = file_get_contents(
      dirname(__DIR__, 2) . '/assets/src/admin/CustomerDirectory.tsx'
    );
    $verificationDirectory = file_get_contents(
      dirname(__DIR__, 2) . '/assets/src/admin/VerificationDirectory.tsx'
    );

    Assert::true(
      is_string($requestState)
      && str_contains($requestState, 'Try again')
      && str_contains($requestState, 'sbay-request-state')
      && is_string($ticketWorkspace)
      && str_contains($ticketWorkspace, 'Tickets could not be loaded')
      && str_contains($ticketWorkspace, 'No matching tickets')
      && str_contains($ticketWorkspace, 'No tickets yet')
      && str_contains($ticketWorkspace, 'Reset filters')
      && is_string($adminApp)
      && str_contains($adminApp, 'Customer profile could not be loaded')
      && str_contains($adminApp, 'Ticket could not be loaded')
      && is_string($customerDirectory)
      && str_contains($customerDirectory, 'Customers could not be loaded')
      && str_contains($customerDirectory, 'No matching customers')
      && is_string($verificationDirectory)
      && str_contains($verificationDirectory, 'Verifications could not be loaded')
      && str_contains($verificationDirectory, 'No purchase verifications yet')
      && is_string($ticketReportWorkspace)
      && str_contains($ticketReportWorkspace, 'Ticket report could not be loaded')
      && is_string($generalWorkspace)
      && str_contains($generalWorkspace, 'General settings unavailable')
      && is_string($securityWorkspace)
      && str_contains($securityWorkspace, 'Security settings unavailable'),
      'Administrator request failures and empty ticket queues provide recoverable, actionable states.'
    );

    Assert::true(
      is_string($reportsWorkspace)
      && str_contains($reportsWorkspace, '<TicketReportWorkspace/>')
      && ! str_contains($reportsWorkspace, 'Notification Delivery')
      && is_string($ticketReportWorkspace)
      && str_contains($ticketReportWorkspace, 'reports/tickets?${query()}')
      && str_contains($ticketReportWorkspace, 'reports/tickets/export?${query()}')
      && str_contains($ticketReportWorkspace, 'admin/tickets/options')
      && str_contains($ticketReportWorkspace, 'Daily support activity')
      && str_contains($ticketReportWorkspace, 'By department')
      && str_contains($ticketReportWorkspace, 'By category')
      && str_contains($ticketReportWorkspace, 'By tag')
      && str_contains($ticketReportWorkspace, 'By custom field value')
      && str_contains($ticketReportWorkspace, 'By agent')
      && str_contains($ticketReportWorkspace, 'category_id')
      && str_contains($ticketReportWorkspace, "params.set('tag_id'")
      && str_contains($ticketReportWorkspace, "params.set('custom_field_id'")
      && str_contains($ticketReportWorkspace, "params.set('custom_field_value'")
      && str_contains($ticketReportWorkspace, 'All tags')
      && str_contains($ticketReportWorkspace, 'Uncategorized')
      && str_contains($ticketReportWorkspace, 'window.setTimeout(() => void load(), 250)')
      && ! str_contains($ticketReportWorkspace, 'Apply report')
      && str_contains($ticketReportWorkspace, 'Clear report filters')
      && str_contains($ticketReportWorkspace, 'options.departments.length?')
      && str_contains($ticketReportWorkspace, 'options.categories.length?')
      && str_contains($ticketReportWorkspace, 'options.tags.length?')
      && str_contains($ticketReportWorkspace, 'options.custom_fields.length?')
      && str_contains($ticketReportWorkspace, 'options.agents.length?')
      && str_contains($ticketReportWorkspace, 'Report period')
      && str_contains($ticketReportWorkspace, 'Last 60 Days')
      && str_contains($ticketReportWorkspace, 'Last 30 Days')
      && str_contains($ticketReportWorkspace, 'Last 14 Days')
      && str_contains($ticketReportWorkspace, 'Last 7 Days')
      && str_contains($ticketReportWorkspace, 'is-need-reply')
      && str_contains($ticketReportWorkspace, 'is-closed')
      && str_contains($ticketReportWorkspace, 'rotate(45)')
      && ! str_contains($ticketReportWorkspace, 'First-response SLA')
      && ! str_contains($ticketReportWorkspace, 'response_bands'),
      'Reports renders ticket performance without deferred notification delivery or SLA analysis.'
    );

    Assert::true(
      is_string($autoCloseWorkspace)
      && str_contains($settingsWorkspace, '<AutoCloseWorkspace/>')
      && str_contains($autoCloseWorkspace, 'Auto-Close Inactive Tickets')
      && str_contains($autoCloseWorkspace, 'Auto-Trash Closed Tickets')
      && str_contains($autoCloseWorkspace, 'Auto-Delete Trashed Tickets')
      && str_contains($autoCloseWorkspace, 'Exclude Tags')
      && str_contains($autoCloseWorkspace, 'This action is irreversible.'),
      'Settings includes the three-stage automated ticket lifecycle policy.'
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

    Assert::true(
      str_contains($ticketConversation, "value={ticket.assigned_agent_id??''}")
      && str_contains($ticketConversation, "mutate('assignment'")
      && str_contains($ticketConversation, '<option value="">Unassigned</option>'),
      'Ticket details show the current owner and allow authorized staff handoff or unassignment.',
    );

    Assert::true(
      is_string($adminApp)
      && str_contains($adminApp, 'loadTicketDetail(true)')
      && str_contains($adminApp, 'detailMutationPending.current')
      && str_contains($adminApp, 'config.ticketListAutoRefreshInterval')
      && str_contains($adminApp, "lazy(() => import('./ReportsWorkspace')")
      && str_contains($adminApp, "lazy(() => import('./SettingsWorkspace')")
      && is_string($adminPageSource)
      && str_contains($adminPageSource, "\$section === 'settings' || (\$section === 'tickets' && \$ticketId > 0)")
      && str_contains($adminPageSource, "if (\$section === 'settings')"),
      'Agent detail refreshes safely while large workspaces and WordPress editor dependencies load only when required.',
    );

    Assert::true(
      str_contains($ticketConversation, 'context.custom_fields.map')
      && str_contains($ticketConversation, "mutate('custom_field'")
      && str_contains($ticketConversation, 'field.type===\'select\'')
      && str_contains($ticketConversation, 'Inactive historical field')
      && str_contains($ticketConversation, 'Custom Fields'),
      'Agent ticket detail renders typed custom fields and protected value mutations.'
    );

    $ticketWorkspace = file_get_contents(
      dirname(__DIR__, 2) . '/assets/src/shared/tickets/TicketWorkspace.tsx'
    );
    Assert::true(
      is_string($ticketWorkspace)
      && ! str_contains($ticketWorkspace, 'sla_state: query.slaState')
      && ! str_contains($ticketWorkspace, 'SLA Due First')
      && ! str_contains($ticketWorkspace, 'SLA Breached'),
      'Shared ticket workspace omits deferred SLA controls.',
    );

    Assert::true(
      str_contains($ticketWorkspace, 'tag_id: query.tagId')
      && str_contains($ticketWorkspace, 'All Tags')
      && str_contains($ticketWorkspace, 'tag_add:')
      && str_contains($ticketWorkspace, 'tag_remove:')
      && is_string($ticketConversation)
      && str_contains($ticketConversation, "mutate('tag_add'")
      && str_contains($ticketConversation, "mutate('tag_remove'"),
      'Staff ticket queue and conversation expose shared tag filtering and mutations.',
    );

    Assert::true(
      str_contains($ticketWorkspace, 'custom_field_id: query.customFieldId')
      && str_contains($ticketWorkspace, 'custom_field_value: query.customFieldValue')
      && str_contains($ticketWorkspace, 'All Custom Fields')
      && str_contains($ticketWorkspace, 'Exact custom field value')
      && str_contains($ticketWorkspace, "selectedCustomField?.type==='checkbox'")
      && str_contains($ticketWorkspace, "mode==='staff'&&query.customFieldId")
      && str_contains($ticketWorkspace, 'custom_field:${field.id}')
      && str_contains($ticketWorkspace, 'Bulk custom field value')
      && str_contains($ticketWorkspace, "field_id: Number(value)")
      && str_contains($ticketWorkspace, 'Leave empty to clear'),
      'Shared ticket workspace exposes staff-only, type-aware custom-field filters and bulk mutations.',
    );

    Assert::true(
      str_contains($ticketWorkspace, "(options?.departments.length ?? 0) > 1")
      && str_contains($ticketWorkspace, "(options?.categories.length ?? 0) > 0")
      && str_contains($ticketWorkspace, "(options?.tags.length ?? 0) > 0")
      && str_contains($ticketWorkspace, "(options?.custom_fields.length ?? 0) > 0")
      && str_contains($ticketWorkspace, 'showDepartments&&ticket.department_name')
      && str_contains($ticketWorkspace, "showCategories?` · \${ticket.category_name||'Uncategorized'}`:''"),
      'Staff queues hide taxonomy metadata, filters, and bulk groups until meaningful configuration exists.',
    );

    Assert::true(
      str_contains($ticketWorkspace, 'ticket.customer_avatar_url?<img')
      && str_contains($ticketWorkspace, 'sbay-ticket-excerpt')
      && str_contains($ticketWorkspace, 'ticket.latest_reply_excerpt')
      && str_contains($ticketWorkspace, 'sbay-ticket-replies'),
      'Staff ticket rows render WordPress customer avatars, latest reply excerpts, and reply counts.',
    );

    $queueItem = file_get_contents(
      dirname(__DIR__, 2) . '/includes/Modules/Tickets/Data/TicketQueueItem.php'
    );
    Assert::true(
      is_string($queueItem)
      && str_contains($queueItem, "isset(\$this->row['latest_reply_excerpt'])")
      && str_contains($queueItem, "! empty(\$this->row['customer_avatar_url'])")
      && str_contains($queueItem, 'get_avatar_url')
      && ! str_contains($queueItem, "'force_default' => true")
      && str_contains($ticketConversation, 'context.customer.avatar_url?<img'),
      'Staff queue tag decoration preserves normalized reply excerpts and WordPress avatar URLs.',
    );

    Assert::true(
      is_string($settingsWorkspace)
      && str_contains($settingsWorkspace, 'Email Notifications')
      && ! str_contains($settingsWorkspace, 'Delivery Logs')
      && str_contains($settingsWorkspace, 'Saved Replies')
      && str_contains($settingsWorkspace, 'Categories')
      && str_contains($settingsWorkspace, 'Tags')
      && str_contains($settingsWorkspace, 'Custom Fields')
      && str_contains($settingsWorkspace, 'Assign Rules')
      && str_contains($settingsWorkspace, 'User Roles')
      && str_contains($settingsWorkspace, 'Departments')
      && ! str_contains($settingsWorkspace, 'Ticket SLA')
      && str_contains($settingsWorkspace, 'Integrations')
      && str_contains($settingsWorkspace, '<NotificationTemplateWorkspace/>')
      && ! str_contains($settingsWorkspace, '<NotificationLogWorkspace/>')
      && str_contains($settingsWorkspace, '<SavedReplyWorkspace/>')
      && str_contains($settingsWorkspace, '<CategoryWorkspace/>')
      && str_contains($settingsWorkspace, '<TagWorkspace/>')
      && str_contains($settingsWorkspace, '<CustomFieldWorkspace/>')
      && str_contains($settingsWorkspace, '<AssignRuleWorkspace/>')
      && str_contains($settingsWorkspace, '<RoleWorkspace/>')
      && str_contains($settingsWorkspace, '<DepartmentWorkspace/>')
      && str_contains($settingsWorkspace, '<GeneralWorkspace')
      && str_contains($settingsWorkspace, '<SecurityWorkspace')
      && str_contains($settingsWorkspace, 'aria-expanded={integrationsOpen}')
      && str_contains($settingsWorkspace, 'sbay-settings-subnav')
      && str_contains($settingsWorkspace, "url.searchParams.set('settings','integrations')")
      && str_contains($settingsWorkspace, "url.searchParams.set('integration','envato')")
      && str_contains($settingsWorkspace, "'login-with-envato'")
      && str_contains($settingsWorkspace, "window.addEventListener('popstate'")
      && str_contains($settingsWorkspace, '<EnvatoLoginWorkspace tab={envatoTab}'),
      'Settings provides shared navigation for notification templates and integrations.'
    );

    Assert::true(
      is_string($assignRuleWorkspace)
      && str_contains($assignRuleWorkspace, "adminGet<Rule[]>('admin/assign-rules')")
      && str_contains($assignRuleWorkspace, 'Bulk Actions')
      && str_contains($assignRuleWorkspace, 'Choose Category')
      && str_contains($assignRuleWorkspace, 'All Categories')
      && str_contains($assignRuleWorkspace, 'sbay-category-chip')
      && str_contains($assignRuleWorkspace, 'Search categories')
      && str_contains($assignRuleWorkspace, 'sbay-general-toggle')
      && str_contains($assignRuleWorkspace, 'value="activate"')
      && str_contains($assignRuleWorkspace, 'value="deactivate"')
      && str_contains($assignRuleWorkspace, 'value="delete"'),
      'Settings includes server-driven assignment rules with category selection and lifecycle bulk actions.'
    );

    Assert::true(
      is_string($weekendWorkspace)
      && str_contains($settingsWorkspace, '<WeekendHolidayWorkspace/>')
      && str_contains($weekendWorkspace, 'Weekend Status')
      && str_contains($weekendWorkspace, 'Add Weekend Day')
      && str_contains($weekendWorkspace, 'Holiday Status')
      && str_contains($weekendWorkspace, 'Add Holiday')
      && str_contains($weekendWorkspace, 'Show Portal Notice')
      && str_contains($weekendWorkspace, 'Send Email Notice')
      && str_contains($weekendWorkspace, '{{ticket_user}}')
      && str_contains($weekendWorkspace, '{{site_name}}'),
      'Settings includes WordPress-timezone weekend and holiday availability controls.'
    );

    Assert::true(
      is_string($envatoLoginWorkspace)
      && str_contains($envatoLoginWorkspace, '>Main</button>')
      && str_contains($envatoLoginWorkspace, 'Login with Envato')
      && str_contains($envatoLoginWorkspace, 'Step 2: Create an Envato API Token')
      && str_contains($envatoLoginWorkspace, 'https://build.envato.com/')
      && str_contains($envatoLoginWorkspace, 'Verify purchases of the user’s items')
      && str_contains($envatoLoginWorkspace, 'Copy confirmation URL')
      && str_contains($envatoLoginWorkspace, 'Save Changes')
      && str_contains($envatoLoginWorkspace, 'Discard')
      && str_contains($envatoLoginWorkspace, 'providers/${provider.id}/configuration')
      && is_string($envatoProvider)
      && str_contains($envatoProvider, "label: 'Click to enable'")
      && str_contains($envatoProvider, "key: 'access_token'")
      && str_contains($envatoProvider, "label: 'Purchase Code/Key Field Label'")
      && str_contains($envatoProvider, "defaultValue: 'Envato Purchase Code'")
      && str_contains($envatoProvider, "label: 'Enable License Required'")
      && str_contains($envatoProvider, "label: 'Check Support Expiry'")
      && str_contains($envatoProvider, "label: 'Confirmation URL'")
      && str_contains($envatoProvider, '$this->settings->portalUrl()')
      && str_contains($envatoProvider, "'sbayenvato'")
      && str_contains($envatoProvider, "label: 'Envato Username'")
      && str_contains($envatoProvider, "label: 'OAuth Client ID'")
      && str_contains($envatoProvider, "label: 'Client Secret Key'"),
      'Envato settings provides a schema-driven OAuth login form with copy and save controls.'
    );

    Assert::true(
      is_string($securityWorkspace)
      && str_contains($securityWorkspace, 'reCAPTCHA (v3)')
      && str_contains($securityWorkspace, 'recaptcha_v3_secret_configured')
      && str_contains($securityWorkspace, 'Show in Login Form')
      && str_contains($securityWorkspace, 'Show in Ticket Form (If not logged in)')
      && str_contains($securityWorkspace, 'Show in Registration Form')
      && str_contains($securityWorkspace, 'Hide reCAPTCHA Badge.')
      && str_contains($securityWorkspace, 'Save Changes')
      && str_contains($securityWorkspace, 'Discard'),
      'Security settings exposes a secret-safe reCAPTCHA v3 configuration tab.',
    );

    Assert::true(
      is_string($generalWorkspace)
      && str_contains($generalWorkspace, "adminGet<GeneralSettings>('settings/general')")
      && str_contains($generalWorkspace, "adminPut<GeneralSettings>('settings/general'")
      && str_contains($generalWorkspace, 'Override WordPress registration setting.')
      && str_contains($generalWorkspace, 'Disable registration form.')
      && str_contains($generalWorkspace, 'Disable guest ticket creation.')
      && str_contains($generalWorkspace, 'Client User Default Role')
      && str_contains($generalWorkspace, 'value="subscriber"')
      && str_contains($generalWorkspace, 'Support Portal Page')
      && str_contains($generalWorkspace, 'shortcode on other pages')
      && str_contains($generalWorkspace, '[supportbay]')
      && str_contains($generalWorkspace, 'Footer Copyright Text')
      && str_contains($generalWorkspace, 'remove_powered_by_branding')
      && str_contains($generalWorkspace, 'Enable WordPress login &amp; registration.')
      && str_contains($generalWorkspace, 'wordpress_login_url')
      && str_contains($generalWorkspace, 'wordpress_registration_url')
      && str_contains($generalWorkspace, 'Enable WordPress profile link.')
      && str_contains($generalWorkspace, 'Enable Sequential Ticket Track ID.')
      && str_contains($generalWorkspace, 'sequential_track_id_prefix')
      && str_contains($generalWorkspace, 'sequential_track_id_length')
      && str_contains($generalWorkspace, 'Enable ticket list auto-refresh.')
      && str_contains($generalWorkspace, 'Minimum value is 5 seconds.')
      && str_contains($generalWorkspace, 'ticket_list_auto_refresh_interval')
      && str_contains($generalWorkspace, 'Enable smart sorting for need reply filter.')
      && str_contains($generalWorkspace, 'smart_need_reply_sorting_enabled')
      && str_contains($generalWorkspace, 'Dashboard Logo')
      && str_contains($generalWorkspace, 'Portal Logo')
      && str_contains($generalWorkspace, 'dashboard_logo_attachment_id')
      && str_contains($generalWorkspace, 'portal_logo_attachment_id')
      && str_contains($generalWorkspace, "wp?.media")
      && str_contains($generalWorkspace, 'Click to enable file upload.')
      && str_contains($generalWorkspace, 'Max File Size')
      && str_contains($generalWorkspace, 'Allowed File Types')
      && str_contains($generalWorkspace, 'Medical Images')
      && str_contains($generalWorkspace, 'attachment_popup_preview_enabled')
      && str_contains($generalWorkspace, 'Rename Ticket Status Labels')
      && str_contains($generalWorkspace, 'Color Palette')
      && str_contains($generalWorkspace, 'style_palettes')
      && str_contains($generalWorkspace, 'Custom CSS')
      && str_contains($generalWorkspace, 'custom_css')
      && str_contains($generalWorkspace, 'Ticket Form Provider Field Label')
      && str_contains($providerWorkspace, 'Edit provider label')
      && str_contains($providerWorkspace, 'Save label')
      && str_contains($generalWorkspace, 'ticket_status_labels')
      && str_contains($generalWorkspace, 'Save Changes')
      && str_contains($generalWorkspace, 'Discard')
      && ! str_contains($generalWorkspace, 'Save authentication links')
      && str_contains($generalWorkspace, 'Turn OFF to strictly follow'),
      'General settings exposes the documented SupportBay registration override.',
    );

    Assert::true(
      str_contains($ticketWorkspace, 'window.setInterval')
      && str_contains($ticketWorkspace, 'document.hidden')
      && str_contains($ticketWorkspace, 'selected.length>0')
      && str_contains($ticketWorkspace, 'reload(true)'),
      'Shared ticket lists auto-refresh silently without interrupting hidden tabs or active selections.',
    );

    Assert::true(
      str_contains($ticketWorkspace, 'toggleNeedReply')
      && str_contains($ticketWorkspace, "current.orderby === 'need_reply'")
      && str_contains($ticketWorkspace, 'sbay-ticket-need-reply-toggle')
      && str_contains($ticketWorkspace, 'role="switch"')
      && str_contains($ticketWorkspace, 'needReplyFilterEnabled')
      && str_contains($ticketWorkspace, 'ticket.needs_reply?')
      && ! str_contains($ticketWorkspace, '>Need Reply First</option>'),
      'Need Reply smart sorting cannot remain selected after its filter is disabled.',
    );

    Assert::true(
      is_string($departmentWorkspace)
      && str_contains($departmentWorkspace, "adminGet<Department[]>('departments')")
      && str_contains($departmentWorkspace, "adminPost<Department>('departments'")
      && str_contains($departmentWorkspace, 'Support is the permanent fallback')
      && str_contains($departmentWorkspace, "selected.slug==='support'")
      && str_contains($departmentWorkspace, 'adminDelete(`departments/${item.id}`')
      && str_contains($departmentWorkspace, "item.slug!=='support'"),
      'Settings provides department management with a protected Support fallback.',
    );

    Assert::true(
      is_string($roleWorkspace)
      && str_contains($roleWorkspace, "adminGet<SupportRole[]>('roles')")
      && str_contains($roleWorkspace, "adminPost<SupportRole>('roles'")
      && str_contains($roleWorkspace, 'capability_groups')
      && str_contains($roleWorkspace, 'All Capabilities')
      && str_contains($roleWorkspace, 'Assign team members through WordPress Users')
      && str_contains($roleWorkspace, "role.editable?'Edit':'View'")
      && ! str_contains($roleWorkspace, 'Generated from name')
      && str_contains($roleWorkspace, 'Support Agent or Manager.')
      && str_contains($roleWorkspace, 'editing.support_role?')
      && str_contains($roleWorkspace, 'void remove(role)'),
      'Settings provides protected, categorized SupportBay role management with a view-only Administrator role.',
    );

    Assert::true(
      is_string($customFieldWorkspace)
      && str_contains($customFieldWorkspace, "adminGet<CustomField[]>('custom-fields')")
      && str_contains($customFieldWorkspace, "adminPost<CustomField>('custom-fields'")
      && str_contains($customFieldWorkspace, 'adminPut<CustomField>(`custom-fields/${selected.id}`')
      && str_contains($customFieldWorkspace, 'adminDelete(`custom-fields/${selected.id}`')
      && str_contains($customFieldWorkspace, 'Visible to customers')
      && str_contains($customFieldWorkspace, 'One choice per line')
      && str_contains($customFieldWorkspace, 'Create Custom Field')
      && str_contains($customFieldWorkspace, 'cannot change type or be deleted'),
      'Settings provides capability-gated custom-field definition and lifecycle management.'
    );

    Assert::true(
      is_string($tagWorkspace)
      && str_contains($tagWorkspace, "adminGet<Tag[]>('tags')")
      && str_contains($tagWorkspace, "adminPost<Tag>('tags'")
      && str_contains($tagWorkspace, 'adminPut<Tag>(`tags/${selected.id}`')
      && str_contains($tagWorkspace, 'adminDelete(`tags/${selected.id}`')
      && str_contains($tagWorkspace, 'Create Tag')
      && str_contains($tagWorkspace, 'Save Changes')
      && str_contains($tagWorkspace, 'Tags used by tickets cannot be deleted')
      && str_contains($tagWorkspace, 'Inactive tags remain visible on historical tickets'),
      'Settings provides capability-gated tag lifecycle management and safe deletion guidance.'
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

    $adminBundle = '';
    foreach (glob(dirname(__DIR__, 2) . '/assets/dist/*.js') ?: [] as $bundlePath) {
      $adminBundle .= (string) file_get_contents($bundlePath);
    }

    Assert::true(
      is_string($adminBundle)
      && str_contains($adminBundle, 'Notification Templates')
      && str_contains($adminBundle, '/preview')
      && str_contains($adminBundle, '/test-email')
      && str_contains($adminBundle, 'notification-preferences')
      && str_contains($adminBundle, 'Email Preferences')
      && str_contains($adminBundle, 'Send test email')
      && ! str_contains($adminBundle, 'Delivery Logs')
      && ! str_contains($adminBundle, 'Retry delivery')
      && ! str_contains($adminBundle, 'Delivery Report')
      && ! str_contains($adminBundle, 'Daily delivery trend')
      && str_contains($adminBundle, 'Support Tickets Report')
      && str_contains($adminBundle, 'Daily support activity')
      && str_contains($adminBundle, 'Export CSV')
      && ! str_contains($adminBundle, 'First-response SLA')
      && ! str_contains($adminBundle, 'Save SLA policy'),
      'Compiled administrator chunks contain the complete lazy-loaded settings and report workspaces.'
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
