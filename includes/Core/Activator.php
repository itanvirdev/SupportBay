<?php

declare(strict_types=1);

namespace SupportBay\Core;

use SupportBay\Core\Database\DatabaseInstaller;
use SupportBay\Modules\Portal\Http\PortalPage;
use SupportBay\Core\Authorization\CapabilityManager;

final class Activator {
  /**
   * Plugin activation handler
   */
  public static function activate(bool $networkWide = false): void {
    if (is_multisite()) {
      wp_die(
        esc_html__('SupportBay v1 supports single-site WordPress installations only. Multisite activation is not supported.', 'supportbay'),
        esc_html__('SupportBay multisite activation unavailable', 'supportbay'),
        ['back_link' => true],
      );
    }
    DatabaseInstaller::install();

    self::storeVersion();
    self::mergeDefaultOptions();
    self::ensurePortalPage();
    CapabilityManager::register();
    self::logActivation();

    PortalPage::registerRewriteRule();
    flush_rewrite_rules();
  }

  /**
   * Store current plugin version in DB
   */
  private static function storeVersion(): void {
    if (! get_option('sbay_version')) {
      add_option('sbay_version', SBAY_VERSION);
    } else {
      update_option('sbay_version', SBAY_VERSION);
    }
  }

  /**
   * Create default plugin options
   */
  public static function defaultSettings(): array {
    return [
      'ticket_reopen_days' => 30,
      'file_upload_enabled' => true,
      'file_upload_max_size_mb' => 20,
      'file_upload_allowed_groups' => ['photos'],
      'attachment_popup_preview_enabled' => false,
      'ticket_status_labels' => ['open'=>'Open','pending'=>'Pending','answered'=>'Answered','resolved'=>'Resolved','closed'=>'Closed'],
      'recaptcha_v3_enabled' => false,
      'recaptcha_v3_site_key' => '',
      'recaptcha_v3_secret_key' => '',
      'recaptcha_v3_show_login' => true,
      'recaptcha_v3_show_guest_ticket' => true,
      'recaptcha_v3_show_registration' => true,
      'recaptcha_v3_hide_badge' => false,
      'style_palette' => 'emerald',
      'custom_css' => '',
      'purchase_provider_field_label' => 'Purchase provider',
      'rich_text_enabled' => false,
      'registration_override' => false,
      'disable_registration_form' => false,
      'disable_guest_ticket_creation' => true,
      'client_user_default_role' => 'subscriber',
      'support_portal_page_id' => 0,
      'shortcode_mode' => false,
      'footer_copyright_text' => 'Copyright © {year} {site_name}',
      'remove_powered_by_branding' => false,
      'wordpress_auth_enabled' => false,
      'wordpress_login_url' => '',
      'wordpress_registration_url' => '',
      'wordpress_profile_enabled' => false,
      'sequential_track_id_enabled' => false,
      'sequential_track_id_prefix' => 'TKT-',
      'sequential_track_id_length' => 6,
      'ticket_list_auto_refresh_enabled' => true,
      'ticket_list_auto_refresh_interval' => 60,
      'smart_need_reply_sorting_enabled' => true,
      'dashboard_logo_attachment_id' => 0,
      'portal_logo_attachment_id' => 0,
      'delete_data_on_uninstall' => false,
    ];
  }

  public static function mergeDefaultOptions(): void {
    $stored=get_option('sbay_settings',[]);
    $stored=is_array($stored)?$stored:[];
    $merged=array_merge(self::defaultSettings(),$stored);
    if($merged!==$stored){update_option('sbay_settings',$merged,false);}
  }

  public static function ensurePortalPage(): void {
    $settings=get_option('sbay_settings',[]);
    $settings=is_array($settings)?$settings:[];
    $pageId=absint($settings['support_portal_page_id']??0);
    if ($pageId>0&&get_post_status($pageId)==='publish') { return; }
    $page=self::ownedPortalPage();
    if (! $page) {
      $created=wp_insert_post(['post_title'=>'Support','post_name'=>'support','post_content'=>'[supportbay]','post_status'=>'publish','post_type'=>'page','meta_input'=>['_sbay_portal_page'=>'1']]);
      $pageId=is_wp_error($created)?0:(int)$created;
    } else { $pageId=(int)$page->ID; }
    if ($pageId>0) {
      $settings['support_portal_page_id']=$pageId;
      update_option('sbay_settings',$settings,false);
    }
  }

  private static function ownedPortalPage(): ?\WP_Post {
    $pages=get_posts(['post_type'=>'page','post_status'=>'publish','numberposts'=>1,'meta_key'=>'_sbay_portal_page','meta_value'=>'1']);
    return isset($pages[0])&&$pages[0] instanceof \WP_Post?$pages[0]:null;
  }

  /**
   * Log activation event (for debugging / analytics later)
   */
  private static function logActivation(): void {
    if (defined('WP_DEBUG') && WP_DEBUG) {
      error_log('[SupportBay] Plugin activated: ' . SBAY_VERSION);
    }
  }
}
