<?php

declare(strict_types=1);

namespace SupportBay\Core;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SupportBay\Core\Database\MigrationRegistry;

final class Uninstaller {
  private const CRON_HOOKS=['sbay_ticket_lifecycle_cleanup'];
  private const OPTIONS=['sbay_settings','sbay_version','sbay_db_version','sbay_role_defaults_version','sbay_portal_rewrite_version','sbay_assign_rule_defaults_installed','sbay_role_metadata','sbay_notification_templates','sbay_notification_preferences','sbay_auto_close_settings','sbay_weekend_holiday_settings'];

  public static function uninstall():void{
    $capability=is_multisite()?'manage_network_plugins':'activate_plugins';
    if(!current_user_can($capability)){return;}
    self::clearCronJobs();
    $settings=get_option('sbay_settings',[]);
    if(!is_array($settings)||!($settings['delete_data_on_uninstall']??false)){return;}
    self::deletePortalPage();self::deleteAttachmentStorage();self::dropTables();self::removeRolesAndCapabilities();
    foreach(self::OPTIONS as $option){delete_option($option);}
    if(defined('WP_DEBUG')&&WP_DEBUG){error_log('[SupportBay] Plugin data removed during uninstall: '.SBAY_VERSION);}
  }

  private static function clearCronJobs():void{foreach(self::CRON_HOOKS as $hook){wp_clear_scheduled_hook($hook);}}

  private static function dropTables():void{
    global $wpdb;
    foreach(array_reverse(MigrationRegistry::tables()) as $schema){
      $table=$schema::tableName();
      if(preg_match('/^'.preg_quote($wpdb->prefix,'/').'sbay_[a-z0-9_]+$/',$table)===1){$wpdb->query("DROP TABLE IF EXISTS `{$table}`");}
    }
  }

  private static function removeRolesAndCapabilities():void{
    foreach(wp_roles()->roles as $slug=>$definition){
      if(str_starts_with((string)$slug,'sbay_')){remove_role((string)$slug);continue;}
      $role=get_role((string)$slug);if(!$role){continue;}
      foreach(array_keys((array)($definition['capabilities']??[])) as $capability){if(str_starts_with((string)$capability,'sbay_')){$role->remove_cap((string)$capability);}}
    }
  }

  private static function deletePortalPage():void{
    $pageIds=get_posts(['post_type'=>'page','post_status'=>['publish','draft','pending','private','future','trash'],'numberposts'=>-1,'fields'=>'ids','meta_key'=>'_sbay_portal_page','meta_value'=>'1']);
    foreach($pageIds as $pageId){wp_delete_post((int)$pageId,true);}
  }

  private static function deleteAttachmentStorage():void{
    $uploads=wp_upload_dir();$paths=[trailingslashit(dirname(untrailingslashit(ABSPATH))).'supportbay-private',empty($uploads['basedir'])?'':trailingslashit((string)$uploads['basedir']).'supportbay'];
    foreach($paths as $path){if($path!==''&&in_array(basename($path),['supportbay-private','supportbay'],true)){self::removeDirectory($path);}}
  }

  private static function removeDirectory(string $path):void{
    if(!is_dir($path)){return;}
    $iterator=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);
    foreach($iterator as $item){$item->isDir()?rmdir($item->getPathname()):wp_delete_file($item->getPathname());}
    rmdir($path);
  }
}
