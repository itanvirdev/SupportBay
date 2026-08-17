<?php

declare(strict_types=1);

namespace SupportBay\Modules\Settings\Services;

use SupportBay\Modules\Settings\Repositories\GeneralSettingsRepository;

final class GeneralSettingsService {
  public function __construct(private readonly GeneralSettingsRepository $repository) {}

  /** @return array<string, mixed> */
  public function get(): array {
    $settings = $this->repository->all();
    $override = (bool) ($settings['registration_override'] ?? false);
    $disabled = (bool) ($settings['disable_registration_form'] ?? false);
    $wordpress = (bool) get_option('users_can_register');
    $role = sanitize_key((string)($settings['client_user_default_role']??'subscriber'));
    if (! get_role($role)) { $role='subscriber'; }
    $pageId=absint($settings['support_portal_page_id']??0);
    if ($pageId>0&&get_post_status($pageId)!=='publish') { $pageId=0; }
    return [
      'registration_override'=>$override,
      'disable_registration_form'=>$disabled,
      'disable_guest_ticket_creation'=>(bool)($settings['disable_guest_ticket_creation']??true),
      'client_user_default_role'=>$role,
      'role_options'=>$this->roleOptions(),
      'support_portal_page_id'=>$pageId,
      'support_portal_url'=>$this->portalUrl($pageId),
      'shortcode_mode'=>(bool)($settings['shortcode_mode']??false),
      'page_options'=>$this->pageOptions(),
      'wordpress_registration_enabled'=>$wordpress,
      'registration_enabled'=>!$disabled&&($override||$wordpress),
    ];
  }

  public function registrationEnabled(): bool {
    $settings=$this->repository->all();
    return !($settings['disable_registration_form']??false)&&((bool)($settings['registration_override']??false)||(bool)get_option('users_can_register'));
  }
  public function clientUserDefaultRole(): string {
    $role=sanitize_key((string)($this->repository->all()['client_user_default_role']??'subscriber'));
    return get_role($role)?$role:'subscriber';
  }
  public function guestTicketCreationEnabled(): bool { return !(bool)($this->repository->all()['disable_guest_ticket_creation']??true); }
  public function portalPageId(): int {
    $id=absint($this->repository->all()['support_portal_page_id']??0);
    return $id>0&&get_post_status($id)==='publish'?$id:0;
  }
  public function shortcodeMode(): bool { return (bool)($this->repository->all()['shortcode_mode']??false); }
  public function portalUrl(?int $pageId=null): string {
    $pageId??=absint($this->repository->all()['support_portal_page_id']??0);
    $permalink=$pageId>0?get_permalink($pageId):false;
    return is_string($permalink)&&$permalink!==''?$permalink:home_url('/');
  }

  /** @param array<string, mixed> $data */
  public function update(array $data): array {
    $settings = $this->repository->all();
    $refreshRewrites = array_key_exists('support_portal_page_id', $data) || array_key_exists('shortcode_mode', $data);
    if (array_key_exists('registration_override', $data)) {
      $settings['registration_override'] = filter_var($data['registration_override'], FILTER_VALIDATE_BOOL);
    }
    if (array_key_exists('disable_registration_form', $data)) {
      $settings['disable_registration_form'] = filter_var($data['disable_registration_form'], FILTER_VALIDATE_BOOL);
    }
    if (array_key_exists('disable_guest_ticket_creation', $data)) {
      $settings['disable_guest_ticket_creation'] = filter_var($data['disable_guest_ticket_creation'], FILTER_VALIDATE_BOOL);
    }
    if (array_key_exists('client_user_default_role',$data)) {
      $role=sanitize_key((string)$data['client_user_default_role']);
      if (get_role($role)) { $settings['client_user_default_role']=$role; }
    }
    if (array_key_exists('support_portal_page_id',$data)) {
      $pageId=absint($data['support_portal_page_id']);
      $settings['support_portal_page_id']=$pageId>0&&get_post_status($pageId)==='publish'?$pageId:0;
    }
    if (array_key_exists('shortcode_mode',$data)) {
      $settings['shortcode_mode']=filter_var($data['shortcode_mode'],FILTER_VALIDATE_BOOL);
    }
    $this->repository->save($settings);
    if ($refreshRewrites) { flush_rewrite_rules(false); }
    return $this->get();
  }

  /** @return array<int, array{slug:string,name:string}> */
  private function roleOptions(): array {
    $options=[];
    foreach (wp_roles()->role_names as $slug=>$name) {
      $options[]=['slug'=>sanitize_key((string)$slug),'name'=>translate_user_role((string)$name)];
    }
    usort($options,static fn(array $left,array $right):int=>strcasecmp($left['name'],$right['name']));
    return $options;
  }

  /** @return array<int, array{id:int,title:string,url:string}> */
  private function pageOptions(): array {
    return array_map(static fn(\WP_Post $page):array=>['id'=>(int)$page->ID,'title'=>sanitize_text_field($page->post_title),'url'=>esc_url_raw((string)get_permalink($page))],get_pages(['post_status'=>'publish','sort_column'=>'post_title']));
  }
}
