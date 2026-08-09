<?php

declare(strict_types=1);

namespace SupportBay\Modules\Admin;

use SupportBay\Core\Authorization\CapabilityManager;

final class AdminPage {
  private const SLUG = 'supportbay';
  private const HOOK = 'toplevel_page_supportbay';

  public function register(): void {
    add_action('admin_menu', [$this, 'registerMenu']);
    add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
  }

  public function registerMenu(): void {
    add_menu_page(
      __('SupportBay', 'supportbay'),
      __('SupportBay', 'supportbay'),
      'sbay_access_dashboard',
      self::SLUG,
      [$this, 'render'],
      'dashicons-sos',
      26,
    );
  }

  public function enqueueAssets(string $hook): void {
    if ($hook !== self::HOOK) {
      return;
    }

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
        'adminUrl'  => esc_url_raw(admin_url('admin.php?page=' . self::SLUG)),
        'userName'  => sanitize_text_field(wp_get_current_user()->display_name),
      ]) . ';',
      'before',
    );
  }

  public function render(): void {
    if (! current_user_can(CapabilityManager::VIEW_TICKETS)) {
      wp_die(esc_html__('You are not allowed to access SupportBay.', 'supportbay'));
    }

    echo '<div class="wrap"><div id="supportbay-admin-app"></div></div>';
  }
}
