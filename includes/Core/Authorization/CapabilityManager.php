<?php

declare(strict_types=1);

namespace SupportBay\Core\Authorization;

final class CapabilityManager {
  private const ROLE_DEFAULTS_VERSION = '1.0.0';
  public const MANAGE_CUSTOMERS = 'sbay_manage_customers';
  public const VIEW_TICKETS = 'sbay_view_tickets';
  public const REPLY_TICKET = 'sbay_reply_ticket';
  public const CREATE_INTERNAL_NOTE = 'sbay_create_internal_note';
  public const CHANGE_TICKET_STATUS = 'sbay_change_ticket_status';
  public const MANAGE_DEPARTMENTS = 'sbay_manage_departments';
  public const CREATE_DEPARTMENT = 'sbay_create_department';
  public const EDIT_DEPARTMENT = 'sbay_edit_department';
  public const MANAGE_PROVIDERS = 'sbay_manage_providers';
  public const VIEW_REPORTS = 'sbay_view_reports';
  public const EXPORT_REPORTS = 'sbay_export_reports';
  public const MANAGE_SETTINGS = 'sbay_manage_settings';
  public const VIEW_VERIFICATIONS = 'sbay_view_purchase_verification';
  public const REFRESH_VERIFICATION = 'sbay_refresh_verification';
  public const USE_SAVED_REPLIES = 'sbay_use_saved_replies';
  public const MANAGE_SAVED_REPLIES = 'sbay_manage_saved_replies';
  public const MANAGE_CATEGORIES = 'sbay_manage_categories';
  public const CHANGE_TICKET_CATEGORY = 'sbay_change_ticket_category';
  public const MANAGE_TAGS = 'sbay_manage_tags';
  public const CHANGE_TICKET_TAGS = 'sbay_change_ticket_tags';
  public const MANAGE_CUSTOM_FIELDS = 'sbay_manage_custom_fields';
  public const CHANGE_TICKET_CUSTOM_FIELDS = 'sbay_change_ticket_custom_fields';
  public const MANAGE_ROLES = 'sbay_manage_roles';

  /** Register protected SupportBay roles and capabilities. */
  public static function register(): void {
    $customer = [
      'read', 'sbay_view_own_tickets', 'sbay_create_ticket',
      'sbay_reply_ticket', 'sbay_upload_attachment',
      'sbay_view_own_profile', 'sbay_edit_own_profile',
    ];
    $agent = [
      'read', 'sbay_access_dashboard', 'sbay_access_agent_dashboard',
      self::VIEW_TICKETS, self::REPLY_TICKET, self::CREATE_INTERNAL_NOTE,
      'sbay_upload_attachment', self::CHANGE_TICKET_STATUS,
      'sbay_change_ticket_priority', 'sbay_move_ticket_department',
      self::CHANGE_TICKET_CATEGORY,
      self::CHANGE_TICKET_TAGS,
      self::CHANGE_TICKET_CUSTOM_FIELDS,
      'sbay_take_ticket_ownership', 'sbay_assign_ticket', self::VIEW_VERIFICATIONS,
      self::USE_SAVED_REPLIES,
    ];
    $manager = array_merge($agent, [
      'sbay_reassign_ticket', 'sbay_escalate_ticket',
      'sbay_merge_ticket',
      'sbay_split_ticket',
      self::MANAGE_DEPARTMENTS, self::CREATE_DEPARTMENT,
      self::EDIT_DEPARTMENT, 'sbay_disable_department',
      self::VIEW_REPORTS, self::REFRESH_VERIFICATION,
      'sbay_manage_agents', self::MANAGE_CUSTOMERS,
      self::MANAGE_SAVED_REPLIES,
      self::MANAGE_CATEGORIES,
      self::MANAGE_TAGS,
      self::MANAGE_CUSTOM_FIELDS,
    ]);
    $administrator = array_values(array_unique(array_merge(
      $customer,
      $manager,
      [
        self::MANAGE_PROVIDERS, self::MANAGE_SETTINGS,
        self::MANAGE_ROLES, 'sbay_manage_capabilities',
        self::EXPORT_REPORTS, 'sbay_download_attachment',
        'sbay_delete_attachment', 'sbay_close_ticket', 'sbay_reopen_ticket',
      ],
    )));

    if (get_option('sbay_role_defaults_version') !== self::ROLE_DEFAULTS_VERSION) {
      self::ensureRole('sbay_customer', __('SupportBay Customer', 'supportbay'), $customer);
      self::ensureRole('sbay_agent', __('Support Agent', 'supportbay'), $agent);
      self::ensureRole('sbay_manager', __('Support Manager', 'supportbay'), $manager);
      update_option('sbay_role_defaults_version', self::ROLE_DEFAULTS_VERSION, false);
    }
    self::grant('administrator', $administrator);
  }

  /** @param string[] $capabilities */
  private static function ensureRole(
    string $slug,
    string $name,
    array $capabilities,
  ): void {
    if (! get_role($slug)) {
      add_role($slug, $name, ['read' => true]);
    }

    self::grant($slug, $capabilities);
  }

  /** @param string[] $capabilities */
  private static function grant(string $roleName, array $capabilities): void {
    $role = get_role($roleName);

    if (! $role) {
      return;
    }

    foreach ($capabilities as $capability) {
      if (! $role->has_cap($capability)) {
        $role->add_cap($capability);
      }
    }
  }
}
