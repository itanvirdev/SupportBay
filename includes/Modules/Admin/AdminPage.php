<?php

declare(strict_types=1);

namespace SupportBay\Modules\Admin;

use SupportBay\Core\Authorization\CapabilityManager;

final class AdminPage {
  private const TICKETS_SLUG = 'supportbay';
  private const REPORTS_SLUG = 'supportbay-reports';
  private const SETTINGS_SLUG = 'supportbay-settings';

  /** @var array<string, string> */
  private const SECTIONS_BY_HOOK = [
    'toplevel_page_supportbay' => 'tickets',
    'supportbay_page_supportbay-reports' => 'reports',
    'supportbay_page_supportbay-settings' => 'settings',
  ];

  public function register(): void {
    add_action('admin_menu', [$this, 'registerMenu']);
    add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
  }

  public function registerMenu(): void {
    add_menu_page(
      __('SupportBay', 'supportbay'),
      __('SupportBay', 'supportbay'),
      'sbay_access_dashboard',
      self::TICKETS_SLUG,
      [$this, 'render'],
      'dashicons-sos',
      26,
    );

    add_submenu_page(
      self::TICKETS_SLUG,
      __('Support Tickets', 'supportbay'),
      __('Support Tickets', 'supportbay'),
      CapabilityManager::VIEW_TICKETS,
      self::TICKETS_SLUG,
      [$this, 'render'],
    );
    add_submenu_page(
      self::TICKETS_SLUG,
      __('Reports', 'supportbay'),
      __('Reports', 'supportbay'),
      CapabilityManager::VIEW_REPORTS,
      self::REPORTS_SLUG,
      [$this, 'render'],
    );
    add_submenu_page(
      self::TICKETS_SLUG,
      __('Settings', 'supportbay'),
      __('Settings', 'supportbay'),
      CapabilityManager::MANAGE_SETTINGS,
      self::SETTINGS_SLUG,
      [$this, 'render'],
    );
  }

  public function enqueueAssets(string $hook): void {
    $section = self::SECTIONS_BY_HOOK[$hook] ?? null;

    if ($section === null) {
      return;
    }

    wp_enqueue_editor();

    $scriptPath = SBAY_PLUGIN_PATH . 'assets/dist/supportbay-admin.js';
    $stylePath = SBAY_PLUGIN_PATH . 'assets/dist/supportbay-admin.css';

    if (! file_exists($scriptPath) || ! file_exists($stylePath)) {
      return;
    }

    wp_enqueue_style(
      'supportbay-admin',
      SBAY_PLUGIN_URL . 'assets/dist/supportbay-admin.css',
      [],
      (string) filemtime($stylePath),
    );
    wp_enqueue_script(
      'supportbay-admin',
      SBAY_PLUGIN_URL . 'assets/dist/supportbay-admin.js',
      [],
      (string) filemtime($scriptPath),
      true,
    );
    wp_add_inline_script(
      'supportbay-admin',
      'window.supportBayAdmin = ' . wp_json_encode([
        'restUrl'   => esc_url_raw(rest_url('sbay/v1/')),
        'restNonce' => wp_create_nonce('wp_rest'),
        'siteName'  => sanitize_text_field(get_bloginfo('name')),
        'adminUrl'  => esc_url_raw(admin_url('admin.php?page=' . self::TICKETS_SLUG)),
        'userName'  => sanitize_text_field(wp_get_current_user()->display_name),
        'canManageCustomers' => current_user_can(CapabilityManager::MANAGE_CUSTOMERS),
        'canViewVerifications' => current_user_can(CapabilityManager::VIEW_VERIFICATIONS),
        'canExportReports' => current_user_can(CapabilityManager::EXPORT_REPORTS),
        'canManageSavedReplies' => current_user_can(CapabilityManager::MANAGE_SAVED_REPLIES),
        'section'   => $section,
      ]) . ';',
      'before',
    );
  }

  public function render(): void {
    $section = $this->currentSection();

    if (! current_user_can($this->capabilityFor($section))) {
      wp_die(esc_html__('You are not allowed to access SupportBay.', 'supportbay'));
    }

    $links = [
      'tickets' => [__('Support Tickets', 'supportbay'), self::TICKETS_SLUG],
      'reports' => [__('Reports', 'supportbay'), self::REPORTS_SLUG],
      'settings' => [__('Settings', 'supportbay'), self::SETTINGS_SLUG],
    ];

    echo '<div class="wrap sbay-admin-page">';
    echo '<header class="sbay-admin-php-header">';
    echo '<a class="sbay-admin-php-brand" href="' . esc_url(admin_url('admin.php?page=' . self::TICKETS_SLUG)) . '">';
    echo '<span aria-hidden="true">S</span><strong>SupportBay</strong></a>';
    echo '<nav aria-label="' . esc_attr__('SupportBay administration', 'supportbay') . '">';

    foreach ($links as $linkSection => [$label, $slug]) {
      $class = $section === $linkSection ? ' class="is-active" aria-current="page"' : '';
      echo '<a' . $class . ' href="' . esc_url(admin_url('admin.php?page=' . $slug)) . '">' . esc_html($label) . '</a>';
    }

    echo '</nav></header><div id="supportbay-admin-app"></div></div>';
  }

  private function currentSection(): string {
    $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : self::TICKETS_SLUG;

    return match ($page) {
      self::REPORTS_SLUG => 'reports',
      self::SETTINGS_SLUG => 'settings',
      default => 'tickets',
    };
  }

  private function capabilityFor(string $section): string {
    return match ($section) {
      'reports' => CapabilityManager::VIEW_REPORTS,
      'settings' => CapabilityManager::MANAGE_SETTINGS,
      default => CapabilityManager::VIEW_TICKETS,
    };
  }
}
