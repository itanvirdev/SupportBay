<?php

declare(strict_types=1);

namespace SupportBay\Modules\Settings\Services;

use InvalidArgumentException;
use SupportBay\Modules\Settings\Repositories\GeneralSettingsRepository;
use SupportBay\Core\Security\SecretCipher;

final class GeneralSettingsService {
  /** @var array<string, string[]> */
  private const FILE_GROUPS = [
    'photos'=>['jpg','jpeg','png','webp','gif'], 'videos'=>['mp4','webm','mov','avi','ogv'],
    'audios'=>['mp3','wav','aac','ogg','flac','m4a','wma'], 'docs'=>['doc','docx','xls','xlsx'],
    'text'=>['txt'], 'csv'=>['csv'], 'pdf'=>['pdf'], 'zip'=>['zip'], 'json'=>['json'],
    'models'=>['stl'], 'medical'=>['dcm'],
  ];
  private const STATUS_LABELS = ['open'=>'Open','pending'=>'Pending','answered'=>'Answered','resolved'=>'Resolved','closed'=>'Closed'];
  private const STYLE_PALETTES = [
    'emerald'=>['name'=>'Emerald','primary'=>'#216e52','accent'=>'#dff369','dark'=>'#172923'],
    'ocean'=>['name'=>'Ocean','primary'=>'#2563eb','accent'=>'#67e8f9','dark'=>'#172554'],
    'violet'=>['name'=>'Violet','primary'=>'#7c3aed','accent'=>'#ddd6fe','dark'=>'#2e1065'],
    'sunset'=>['name'=>'Sunset','primary'=>'#ea580c','accent'=>'#fed7aa','dark'=>'#431407'],
    'slate'=>['name'=>'Slate','primary'=>'#475569','accent'=>'#cbd5e1','dark'=>'#0f172a'],
  ];
  public function __construct(private readonly GeneralSettingsRepository $repository,private readonly SecretCipher $cipher) {}

  /** @return array<string, mixed> */
  public function get(): array {
    $settings = $this->repository->all();
    $override = (bool) ($settings['registration_override'] ?? false);
    $disabled = (bool) ($settings['disable_registration_form'] ?? false);
    $wordpress = (bool) get_option('users_can_register');
    $role = 'subscriber';
    $pageId=absint($settings['support_portal_page_id']??0);
    if ($pageId>0&&get_post_status($pageId)!=='publish') { $pageId=0; }
    $dashboardLogoId=$this->normalizeLogoAttachmentId($settings['dashboard_logo_attachment_id']??0);
    $portalLogoId=$this->normalizeLogoAttachmentId($settings['portal_logo_attachment_id']??0);
    return [
      'registration_override'=>$override,
      'disable_registration_form'=>$disabled,
      'disable_guest_ticket_creation'=>(bool)($settings['disable_guest_ticket_creation']??true),
      'client_user_default_role'=>$role,
      'role_options'=>$this->roleOptions(),
      'support_portal_page_id'=>$pageId,
      'support_portal_url'=>$this->portalUrl($pageId),
      'shortcode_mode'=>(bool)($settings['shortcode_mode']??false),
      'footer_copyright_text'=>$this->normalizeFooterCopyrightText($settings),
      'remove_powered_by_branding'=>(bool)($settings['remove_powered_by_branding']??false),
      'delete_data_on_uninstall'=>(bool)($settings['delete_data_on_uninstall']??false),
      'wordpress_auth_enabled'=>(bool)($settings['wordpress_auth_enabled']??false),
      'wordpress_login_url'=>esc_url_raw((string)($settings['wordpress_login_url']??'')),
      'wordpress_registration_url'=>esc_url_raw((string)($settings['wordpress_registration_url']??'')),
      'wordpress_profile_enabled'=>(bool)($settings['wordpress_profile_enabled']??false),
      'sequential_track_id_enabled'=>(bool)($settings['sequential_track_id_enabled']??false),
      'sequential_track_id_prefix'=>$this->normalizeTrackIdPrefix((string)($settings['sequential_track_id_prefix']??'TKT-')),
      'sequential_track_id_length'=>$this->normalizeTrackIdLength($settings['sequential_track_id_length']??6),
      'ticket_list_auto_refresh_enabled'=>(bool)($settings['ticket_list_auto_refresh_enabled']??true),
      'ticket_list_auto_refresh_interval'=>$this->normalizeAutoRefreshInterval($settings['ticket_list_auto_refresh_interval']??60),
      'smart_need_reply_sorting_enabled'=>(bool)($settings['smart_need_reply_sorting_enabled']??true),
      'dashboard_logo_attachment_id'=>$dashboardLogoId,
      'dashboard_logo_url'=>$this->logoUrl($dashboardLogoId),
      'portal_logo_attachment_id'=>$portalLogoId,
      'portal_logo_url'=>$this->logoUrl($portalLogoId),
      'default_logo_url'=>$this->defaultLogoUrl(),
      'file_upload_enabled'=>(bool)($settings['file_upload_enabled']??true),
      'file_upload_max_size_mb'=>$this->normalizeFileSize($settings['file_upload_max_size_mb']??20),
      'file_upload_allowed_groups'=>$this->normalizeFileGroups($settings['file_upload_allowed_groups']??['photos']),
      'attachment_popup_preview_enabled'=>(bool)($settings['file_upload_enabled']??true)&&(bool)($settings['attachment_popup_preview_enabled']??false),
      'ticket_status_labels'=>$this->normalizeStatusLabels($settings['ticket_status_labels']??[]),
      'recaptcha_v3_enabled'=>(bool)($settings['recaptcha_v3_enabled']??false),
      'recaptcha_v3_site_key'=>sanitize_text_field((string)($settings['recaptcha_v3_site_key']??'')),
      'recaptcha_v3_secret_key'=>'',
      'recaptcha_v3_secret_configured'=>(string)($settings['recaptcha_v3_secret_key']??'')!=='',
      'recaptcha_v3_show_login'=>(bool)($settings['recaptcha_v3_show_login']??true),
      'recaptcha_v3_show_guest_ticket'=>(bool)($settings['recaptcha_v3_show_guest_ticket']??true),
      'recaptcha_v3_show_registration'=>(bool)($settings['recaptcha_v3_show_registration']??true),
      'recaptcha_v3_hide_badge'=>(bool)($settings['recaptcha_v3_hide_badge']??false),
      'style_palette'=>$this->normalizePalette($settings['style_palette']??'emerald'),
      'style_palettes'=>self::STYLE_PALETTES,
      'custom_css'=>$this->sanitizeCustomCss((string)($settings['custom_css']??'')),
      'purchase_provider_field_label'=>$this->normalizeProviderFieldLabel($settings['purchase_provider_field_label']??'Purchase provider'),
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
    return 'subscriber';
  }
  public function guestTicketCreationEnabled(): bool { return !(bool)($this->repository->all()['disable_guest_ticket_creation']??true); }
  /** @return array{enabled:bool,site_key:string,secret_key:string,show_login:bool,show_registration:bool,show_guest_ticket:bool} */
  public function recaptchaConfiguration(): array {
    $settings=$this->repository->all();
    $encrypted=(string)($settings['recaptcha_v3_secret_key']??'');
    return [
      'enabled'=>(bool)($settings['recaptcha_v3_enabled']??false),
      'site_key'=>sanitize_text_field((string)($settings['recaptcha_v3_site_key']??'')),
      'secret_key'=>$encrypted!==''?$this->cipher->decrypt($encrypted):'',
      'show_login'=>(bool)($settings['recaptcha_v3_show_login']??true),
      'show_registration'=>(bool)($settings['recaptcha_v3_show_registration']??true),
      'show_guest_ticket'=>(bool)($settings['recaptcha_v3_show_guest_ticket']??true),
    ];
  }
  public function portalPageId(): int {
    $id=absint($this->repository->all()['support_portal_page_id']??0);
    return $id>0&&get_post_status($id)==='publish'?$id:0;
  }
  public function shortcodeMode(): bool { return (bool)($this->repository->all()['shortcode_mode']??false); }
  public function footerCopyrightText(): string { return $this->normalizeFooterCopyrightText($this->repository->all()); }
  public function removePoweredByBranding(): bool { return (bool)($this->repository->all()['remove_powered_by_branding']??false); }
  public function wordpressAuthEnabled(): bool { return (bool)($this->repository->all()['wordpress_auth_enabled']??false); }
  public function wordpressLoginUrl(string $redirectTo): string {
    $custom=esc_url_raw((string)($this->repository->all()['wordpress_login_url']??''));
    return $custom!==''?$custom:wp_login_url($redirectTo);
  }
  public function wordpressRegistrationUrl(): string {
    $custom=esc_url_raw((string)($this->repository->all()['wordpress_registration_url']??''));
    return $custom!==''?$custom:wp_registration_url();
  }
  public function wordpressProfileEnabled(): bool { return (bool)($this->repository->all()['wordpress_profile_enabled']??false); }
  public function sequentialTrackIdEnabled(): bool { return (bool)($this->repository->all()['sequential_track_id_enabled']??false); }
  public function sequentialTrackIdPrefix(): string { return $this->normalizeTrackIdPrefix((string)($this->repository->all()['sequential_track_id_prefix']??'TKT-')); }
  public function sequentialTrackIdLength(): int { return $this->normalizeTrackIdLength($this->repository->all()['sequential_track_id_length']??6); }
  public function ticketListAutoRefreshEnabled(): bool { return (bool)($this->repository->all()['ticket_list_auto_refresh_enabled']??true); }
  public function ticketListAutoRefreshInterval(): int { return $this->normalizeAutoRefreshInterval($this->repository->all()['ticket_list_auto_refresh_interval']??60); }
  public function smartNeedReplySortingEnabled(): bool { return (bool)($this->repository->all()['smart_need_reply_sorting_enabled']??true); }
  public function purchaseProviderFieldLabel(): string { return $this->normalizeProviderFieldLabel($this->repository->all()['purchase_provider_field_label']??'Purchase provider'); }
  public function dashboardLogoUrl(): string { return $this->logoUrl($this->normalizeLogoAttachmentId($this->repository->all()['dashboard_logo_attachment_id']??0)); }
  public function portalLogoUrl(): string { return $this->logoUrl($this->normalizeLogoAttachmentId($this->repository->all()['portal_logo_attachment_id']??0)); }
  public function fileUploadEnabled(): bool { return (bool)($this->repository->all()['file_upload_enabled']??true); }
  public function fileUploadMaxSizeMb(): int { return $this->normalizeFileSize($this->repository->all()['file_upload_max_size_mb']??20); }
  /** @return string[] */
  public function allowedFileExtensions(): array {
    $groups=$this->normalizeFileGroups($this->repository->all()['file_upload_allowed_groups']??['photos']);
    return array_values(array_unique(array_merge(...array_map(static fn(string $group):array=>self::FILE_GROUPS[$group],$groups))));
  }
  public function attachmentPopupPreviewEnabled(): bool {
    $settings=$this->repository->all();
    return (bool)($settings['file_upload_enabled']??true)&&(bool)($settings['attachment_popup_preview_enabled']??false);
  }
  /** @return array<string, string> */
  public function ticketStatusLabels(): array { return $this->normalizeStatusLabels($this->repository->all()['ticket_status_labels']??[]); }
  public function supportBayCss(): string {
    $settings=$this->repository->all();$palette=self::STYLE_PALETTES[$this->normalizePalette($settings['style_palette']??'emerald')];
    return ':root,.sbay-admin-page{--sbay-green:'.$palette['primary'].';--sbay-lime:'.$palette['accent'].';--sbay-navy:'.$palette['dark'].';--sbay-brand-primary:'.$palette['primary'].';--sbay-brand-accent:'.$palette['accent'].';--sbay-brand-dark:'.$palette['dark'].';}'.$this->sanitizeCustomCss((string)($settings['custom_css']??''));
  }
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
      $settings['client_user_default_role']='subscriber';
    }
    if (array_key_exists('support_portal_page_id',$data)) {
      $pageId=absint($data['support_portal_page_id']);
      $settings['support_portal_page_id']=$pageId>0&&get_post_status($pageId)==='publish'?$pageId:0;
    }
    if (array_key_exists('shortcode_mode',$data)) {
      $settings['shortcode_mode']=filter_var($data['shortcode_mode'],FILTER_VALIDATE_BOOL);
    }
    if (array_key_exists('footer_copyright_text',$data)) {
      $copyright=sanitize_text_field(wp_unslash((string)$data['footer_copyright_text']));
      $settings['footer_copyright_text']=$copyright!==''?$copyright:'Copyright © {year} {site_name}';
    }
    if (array_key_exists('remove_powered_by_branding',$data)) {
      $settings['remove_powered_by_branding']=filter_var($data['remove_powered_by_branding'],FILTER_VALIDATE_BOOL);
    }
    if (array_key_exists('delete_data_on_uninstall',$data)) {
      $settings['delete_data_on_uninstall']=filter_var($data['delete_data_on_uninstall'],FILTER_VALIDATE_BOOL);
    }
    if (array_key_exists('wordpress_auth_enabled',$data)) {
      $settings['wordpress_auth_enabled']=filter_var($data['wordpress_auth_enabled'],FILTER_VALIDATE_BOOL);
    }
    if (array_key_exists('wordpress_login_url',$data)) {
      $settings['wordpress_login_url']=esc_url_raw(wp_unslash((string)$data['wordpress_login_url']));
    }
    if (array_key_exists('wordpress_registration_url',$data)) {
      $settings['wordpress_registration_url']=esc_url_raw(wp_unslash((string)$data['wordpress_registration_url']));
    }
    if (array_key_exists('wordpress_profile_enabled',$data)) {
      $settings['wordpress_profile_enabled']=filter_var($data['wordpress_profile_enabled'],FILTER_VALIDATE_BOOL);
    }
    if (array_key_exists('sequential_track_id_enabled',$data)) {
      $settings['sequential_track_id_enabled']=filter_var($data['sequential_track_id_enabled'],FILTER_VALIDATE_BOOL);
    }
    if (array_key_exists('sequential_track_id_prefix',$data)) {
      $settings['sequential_track_id_prefix']=$this->normalizeTrackIdPrefix((string)$data['sequential_track_id_prefix']);
    }
    if (array_key_exists('sequential_track_id_length',$data)) {
      $settings['sequential_track_id_length']=$this->normalizeTrackIdLength($data['sequential_track_id_length']);
    }
    if (array_key_exists('ticket_list_auto_refresh_enabled',$data)) {
      $settings['ticket_list_auto_refresh_enabled']=filter_var($data['ticket_list_auto_refresh_enabled'],FILTER_VALIDATE_BOOL);
    }
    if (array_key_exists('ticket_list_auto_refresh_interval',$data)) {
      $settings['ticket_list_auto_refresh_interval']=$this->normalizeAutoRefreshInterval($data['ticket_list_auto_refresh_interval']);
    }
    if (array_key_exists('smart_need_reply_sorting_enabled',$data)) {
      $settings['smart_need_reply_sorting_enabled']=filter_var($data['smart_need_reply_sorting_enabled'],FILTER_VALIDATE_BOOL);
    }
    foreach (['dashboard_logo_attachment_id','portal_logo_attachment_id'] as $logoKey) {
      if (array_key_exists($logoKey,$data)) {
        $settings[$logoKey]=$this->normalizeLogoAttachmentId($data[$logoKey]);
      }
    }
    if (array_key_exists('file_upload_enabled',$data)) { $settings['file_upload_enabled']=filter_var($data['file_upload_enabled'],FILTER_VALIDATE_BOOL); }
    if (array_key_exists('file_upload_max_size_mb',$data)) { $settings['file_upload_max_size_mb']=$this->normalizeFileSize($data['file_upload_max_size_mb']); }
    if (array_key_exists('file_upload_allowed_groups',$data)) { $settings['file_upload_allowed_groups']=$this->normalizeFileGroups($data['file_upload_allowed_groups']); }
    if (array_key_exists('attachment_popup_preview_enabled',$data)) { $settings['attachment_popup_preview_enabled']=filter_var($data['attachment_popup_preview_enabled'],FILTER_VALIDATE_BOOL); }
    if (!(bool)($settings['file_upload_enabled']??true)) { $settings['attachment_popup_preview_enabled']=false; }
    if (array_key_exists('ticket_status_labels',$data)) { $settings['ticket_status_labels']=$this->normalizeStatusLabels($data['ticket_status_labels']); }
    foreach(['recaptcha_v3_enabled','recaptcha_v3_show_login','recaptcha_v3_show_guest_ticket','recaptcha_v3_show_registration','recaptcha_v3_hide_badge'] as $booleanKey){if(array_key_exists($booleanKey,$data)){$settings[$booleanKey]=filter_var($data[$booleanKey],FILTER_VALIDATE_BOOL);}}
    if(array_key_exists('recaptcha_v3_site_key',$data)){$settings['recaptcha_v3_site_key']=sanitize_text_field(wp_unslash((string)$data['recaptcha_v3_site_key']));}
    if(array_key_exists('recaptcha_v3_secret_key',$data)&&trim((string)$data['recaptcha_v3_secret_key'])!==''){$settings['recaptcha_v3_secret_key']=$this->cipher->encrypt(sanitize_text_field(wp_unslash((string)$data['recaptcha_v3_secret_key'])));}
    if((bool)($settings['recaptcha_v3_enabled']??false)&&((string)($settings['recaptcha_v3_site_key']??'')===''||(string)($settings['recaptcha_v3_secret_key']??'')==='')){throw new InvalidArgumentException('Site Key and Secret Key are required to enable reCAPTCHA v3.');}
    if(array_key_exists('style_palette',$data)){$settings['style_palette']=$this->normalizePalette($data['style_palette']);}
    if(array_key_exists('custom_css',$data)){$settings['custom_css']=$this->sanitizeCustomCss((string)$data['custom_css']);}
    if(array_key_exists('purchase_provider_field_label',$data)){$settings['purchase_provider_field_label']=$this->normalizeProviderFieldLabel($data['purchase_provider_field_label']);}
    $this->repository->save($settings);
    if ($refreshRewrites) { flush_rewrite_rules(false); }
    return $this->get();
  }

  /** @return array<int, array{slug:string,name:string}> */
  private function roleOptions(): array {
    return [['slug'=>'subscriber','name'=>translate_user_role('Subscriber')]];
  }

  /** @return array<int, array{id:int,title:string,url:string}> */
  private function pageOptions(): array {
    return array_map(static fn(\WP_Post $page):array=>['id'=>(int)$page->ID,'title'=>sanitize_text_field($page->post_title),'url'=>esc_url_raw((string)get_permalink($page))],get_pages(['post_status'=>'publish','sort_column'=>'post_title']));
  }

  /** @param array<string, mixed> $settings */
  private function normalizeFooterCopyrightText(array $settings): string {
    $text=sanitize_text_field((string)($settings['footer_copyright_text']??'Copyright © {year} {site_name}'));
    return $text!==''?$text:'Copyright © {year} {site_name}';
  }

  private function normalizeTrackIdPrefix(string $prefix): string {
    $prefix=strtoupper(sanitize_text_field(wp_unslash($prefix)));
    $prefix=(string)preg_replace('/[^A-Z0-9_-]/','',$prefix);
    return substr($prefix,0,20);
  }

  private function normalizeLogoAttachmentId(mixed $attachmentId): int {
    $attachmentId=absint($attachmentId);
    return $attachmentId>0&&wp_attachment_is_image($attachmentId)?$attachmentId:0;
  }

  private function logoUrl(int $attachmentId): string {
    $url=$attachmentId>0?wp_get_attachment_image_url($attachmentId,'full'):false;
    return is_string($url)&&$url!==''?esc_url_raw($url):$this->defaultLogoUrl();
  }

  private function defaultLogoUrl(): string {
    return esc_url_raw(SBAY_PLUGIN_URL.'assets/images/supportbay-logo.svg');
  }

  private function normalizeTrackIdLength(mixed $length): int {
    return min(32,max(6,absint($length)));
  }

  private function normalizeAutoRefreshInterval(mixed $interval): int {
    return min(3600,max(5,absint($interval)));
  }

  private function normalizeFileSize(mixed $size): int { return min(100,max(1,absint($size))); }

  /** @return string[] */
  private function normalizeFileGroups(mixed $groups): array {
    $groups=is_array($groups)?$groups:[];
    return array_values(array_intersect(array_keys(self::FILE_GROUPS),array_map(static fn(mixed $group):string=>sanitize_key((string)$group),$groups)));
  }

  /** @return array<string, string> */
  private function normalizeStatusLabels(mixed $labels): array {
    $labels=is_array($labels)?$labels:[];$normalized=[];
    foreach(self::STATUS_LABELS as $status=>$fallback){$label=sanitize_text_field(wp_unslash((string)($labels[$status]??$fallback)));$normalized[$status]=$label!==''?$label:$fallback;}
    return $normalized;
  }

  private function normalizePalette(mixed $palette): string { $palette=sanitize_key((string)$palette);return isset(self::STYLE_PALETTES[$palette])?$palette:'emerald'; }

  private function normalizeProviderFieldLabel(mixed $label): string {
    $label=sanitize_text_field(wp_unslash((string)$label));
    return $label!==''?substr($label,0,100):'Purchase provider';
  }

  private function sanitizeCustomCss(string $css): string {
    $css=wp_strip_all_tags(wp_unslash($css));
    $css=(string)preg_replace('/@import\s+[^;]+;?/i','',$css);
    $css=(string)preg_replace('/(?:expression\s*\(|javascript\s*:|<\/style)/i','',$css);
    return substr(trim($css),0,20000);
  }
}
