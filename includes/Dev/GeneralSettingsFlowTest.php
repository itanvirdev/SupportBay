<?php

declare(strict_types=1);

namespace SupportBay\Dev;

use SupportBay\Core\Testing\Assert;
use SupportBay\Core\Testing\FlowTest;
use SupportBay\Modules\Settings\Services\GeneralSettingsService;

final class GeneralSettingsFlowTest extends FlowTest {
  protected static function title(): string { return 'General Settings Flow Test'; }
  protected static function execute(...$services): void {
    /** @var GeneralSettingsService $settings */
    [$settings]=$services;
    $previousWordPress=get_option('users_can_register');
    $previous=$settings->get();
    $pageId=0;
    try {
      update_option('users_can_register',0);
      Assert::true($settings->update(['registration_override'=>true])['registration_enabled']===true,'SupportBay can override disabled WordPress registration.');
      Assert::true($settings->update(['registration_override'=>false])['registration_enabled']===false,'Disabling the override strictly follows WordPress registration.');
      update_option('users_can_register',1);
      Assert::true($settings->get()['registration_enabled']===true,'Native WordPress registration remains effective without the override.');
      Assert::true($settings->update(['disable_registration_form'=>true])['registration_enabled']===false,'The registration-form switch disables registration regardless of other settings.');
      $updated=$settings->update(['disable_registration_form'=>false,'disable_guest_ticket_creation'=>false,'client_user_default_role'=>'contributor']);
      Assert::true($updated['disable_guest_ticket_creation']===false&&$updated['client_user_default_role']==='contributor','Guest policy and the administrator-selected registration role persist.');
      $branding=$settings->update(['footer_copyright_text'=>'© {year} {site_name}. All rights reserved.','remove_powered_by_branding'=>true]);
      Assert::true($branding['footer_copyright_text']==='© {year} {site_name}. All rights reserved.'&&$branding['remove_powered_by_branding']===true,'Portal copyright text and powered-by visibility persist.');
      $wordpressAuth=$settings->update(['wordpress_auth_enabled'=>true,'wordpress_login_url'=>'https://example.com/customer-login','wordpress_registration_url'=>'https://example.com/customer-register']);
      Assert::true($wordpressAuth['wordpress_auth_enabled']===true&&$wordpressAuth['wordpress_login_url']==='https://example.com/customer-login'&&$wordpressAuth['wordpress_registration_url']==='https://example.com/customer-register','WordPress authentication mode and optional custom links persist.');
      Assert::true($settings->wordpressLoginUrl('https://example.com/support')==='https://example.com/customer-login'&&$settings->wordpressRegistrationUrl()==='https://example.com/customer-register','Custom WordPress authentication links take precedence over native URLs.');
      $settings->update(['wordpress_login_url'=>'','wordpress_registration_url'=>'']);
      Assert::true(str_contains($settings->wordpressLoginUrl('https://example.com/support'),'wp-login.php')&&str_contains($settings->wordpressRegistrationUrl(),'wp-login.php?action=register'),'Native WordPress authentication URLs are used only when custom links are empty.');
      $profile=$settings->update(['wordpress_profile_enabled'=>true]);
      Assert::true($profile['wordpress_profile_enabled']===true&&$settings->wordpressProfileEnabled(),'WordPress profile-link mode persists.');
      $trackIds=$settings->update(['sequential_track_id_enabled'=>true,'sequential_track_id_prefix'=>'tkt-!','sequential_track_id_length'=>6]);
      Assert::true($trackIds['sequential_track_id_enabled']===true&&$trackIds['sequential_track_id_prefix']==='TKT-'&&$trackIds['sequential_track_id_length']===6,'Sequential ticket ID configuration is normalized and persisted.');
      $autoRefresh=$settings->update(['ticket_list_auto_refresh_enabled'=>true,'ticket_list_auto_refresh_interval'=>2]);
      Assert::true($autoRefresh['ticket_list_auto_refresh_enabled']===true&&$autoRefresh['ticket_list_auto_refresh_interval']===5,'Ticket-list auto-refresh enforces its five-second minimum.');
      $smartSorting=$settings->update(['smart_need_reply_sorting_enabled'=>false]);
      Assert::true($smartSorting['smart_need_reply_sorting_enabled']===false&&!$settings->smartNeedReplySortingEnabled(),'Need Reply smart sorting can be explicitly disabled.');
      $logos=$settings->update(['dashboard_logo_attachment_id'=>0,'portal_logo_attachment_id'=>0]);
      Assert::true($logos['dashboard_logo_attachment_id']===0&&$logos['portal_logo_attachment_id']===0&&str_ends_with($logos['dashboard_logo_url'],'assets/images/supportbay-logo.svg')&&str_ends_with($logos['portal_logo_url'],'assets/images/supportbay-logo.svg'),'Dashboard and portal logos fall back to the bundled SupportBay asset.');
      $files=$settings->update(['file_upload_enabled'=>true,'file_upload_max_size_mb'=>150,'file_upload_allowed_groups'=>['photos','pdf','invalid'],'attachment_popup_preview_enabled'=>true]);
      Assert::true($files['file_upload_enabled']===true&&$files['file_upload_max_size_mb']===100&&$files['file_upload_allowed_groups']===['photos','pdf']&&$files['attachment_popup_preview_enabled']===true&&in_array('pdf',$settings->allowedFileExtensions(),true),'Customer file policy is normalized and persisted.');
      $disabledFiles=$settings->update(['file_upload_enabled'=>false,'attachment_popup_preview_enabled'=>true]);
      Assert::true($disabledFiles['attachment_popup_preview_enabled']===false&&!$settings->attachmentPopupPreviewEnabled(),'Attachment popup preview cannot remain enabled when customer uploads are disabled.');
      $statusLabels=$settings->update(['ticket_status_labels'=>['open'=>'New Request','closed'=>'Complete']]);
      Assert::true($statusLabels['ticket_status_labels']['open']==='New Request'&&$statusLabels['ticket_status_labels']['closed']==='Complete'&&$statusLabels['ticket_status_labels']['pending']==='Pending','Ticket status display labels are customizable without changing canonical values.');
      $style=$settings->update(['style_palette'=>'ocean','custom_css'=>'@import url(https://example.com/x.css); .sbay-shell { color: red; } </style>']);
      Assert::true($style['style_palette']==='ocean'&&!str_contains($style['custom_css'],'@import')&&!str_contains($style['custom_css'],'</style>')&&str_contains($settings->supportBayCss(),'--sbay-green:#2563eb'),'Predefined palettes and sanitized SupportBay-only custom CSS persist.');
      $independentShortcode=$settings->update(['support_portal_page_id'=>0,'shortcode_mode'=>true]);
      Assert::true($independentShortcode['support_portal_page_id']===0&&$settings->shortcodeMode(),'Shortcode mode remains independent when no selected portal page is configured.');
      $pageId=(int)wp_insert_post(['post_title'=>'SupportBay Portal Test','post_status'=>'publish','post_type'=>'page']);
      $portal=$settings->update(['support_portal_page_id'=>$pageId,'shortcode_mode'=>true]);
      Assert::true($portal['support_portal_page_id']===$pageId&&$portal['shortcode_mode']===true&&str_contains($portal['support_portal_url'],'supportbay-portal-test'),'A selected portal page and shortcode mode can be configured independently.');
    } finally {
      update_option('users_can_register',$previousWordPress);
      $settings->update([
        'registration_override'=>$previous['registration_override'],
        'disable_registration_form'=>$previous['disable_registration_form'],
        'disable_guest_ticket_creation'=>$previous['disable_guest_ticket_creation'],
        'client_user_default_role'=>$previous['client_user_default_role'],
        'support_portal_page_id'=>$previous['support_portal_page_id'],
        'shortcode_mode'=>$previous['shortcode_mode'],
        'footer_copyright_text'=>$previous['footer_copyright_text'],
        'remove_powered_by_branding'=>$previous['remove_powered_by_branding'],
        'wordpress_auth_enabled'=>$previous['wordpress_auth_enabled'],
        'wordpress_login_url'=>$previous['wordpress_login_url'],
        'wordpress_registration_url'=>$previous['wordpress_registration_url'],
        'wordpress_profile_enabled'=>$previous['wordpress_profile_enabled'],
        'sequential_track_id_enabled'=>$previous['sequential_track_id_enabled'],
        'sequential_track_id_prefix'=>$previous['sequential_track_id_prefix'],
        'sequential_track_id_length'=>$previous['sequential_track_id_length'],
        'ticket_list_auto_refresh_enabled'=>$previous['ticket_list_auto_refresh_enabled'],
        'ticket_list_auto_refresh_interval'=>$previous['ticket_list_auto_refresh_interval'],
        'smart_need_reply_sorting_enabled'=>$previous['smart_need_reply_sorting_enabled'],
        'dashboard_logo_attachment_id'=>$previous['dashboard_logo_attachment_id'],
        'portal_logo_attachment_id'=>$previous['portal_logo_attachment_id'],
        'file_upload_enabled'=>$previous['file_upload_enabled'],
        'file_upload_max_size_mb'=>$previous['file_upload_max_size_mb'],
        'file_upload_allowed_groups'=>$previous['file_upload_allowed_groups'],
        'attachment_popup_preview_enabled'=>$previous['attachment_popup_preview_enabled'],
        'ticket_status_labels'=>$previous['ticket_status_labels'],
        'style_palette'=>$previous['style_palette'],
        'custom_css'=>$previous['custom_css'],
      ]);
      if ($pageId>0) { wp_delete_post($pageId,true); }
    }
  }
}
