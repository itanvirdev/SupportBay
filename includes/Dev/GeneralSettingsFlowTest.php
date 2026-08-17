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
      ]);
      if ($pageId>0) { wp_delete_post($pageId,true); }
    }
  }
}
