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
  public static function activate(): void {
    DatabaseInstaller::install();

    self::storeVersion();
    self::createDefaultOptions();
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
  private static function createDefaultOptions(): void {
    $defaults = [
      'ticket_reopen_days' => 30,
      'default_department' => 'support',
      'file_upload_enabled' => true,
      'rich_text_enabled' => false,
      'registration_override' => false,
      'disable_registration_form' => false,
      'disable_guest_ticket_creation' => true,
      'client_user_default_role' => 'subscriber',
      'support_portal_page_id' => 0,
      'shortcode_mode' => false,
    ];

    if (! get_option('sbay_settings')) {
      add_option('sbay_settings', $defaults);
    }
  }

  public static function ensurePortalPage(): void {
    $settings=get_option('sbay_settings',[]);
    $settings=is_array($settings)?$settings:[];
    $pageId=absint($settings['support_portal_page_id']??0);
    if ($pageId>0&&get_post_status($pageId)==='publish') { return; }
    $page=get_page_by_path('support',OBJECT,'page');
    if (! $page) {
      $created=wp_insert_post(['post_title'=>'Support','post_name'=>'support','post_content'=>'[supportbay]','post_status'=>'publish','post_type'=>'page']);
      $pageId=is_wp_error($created)?0:(int)$created;
    } else { $pageId=(int)$page->ID; }
    if ($pageId>0) {
      $settings['support_portal_page_id']=$pageId;
      update_option('sbay_settings',$settings,false);
    }
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
