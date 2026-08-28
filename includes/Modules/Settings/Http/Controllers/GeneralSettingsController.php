<?php

declare(strict_types=1);

namespace SupportBay\Modules\Settings\Http\Controllers;

use SupportBay\Core\Authorization\CapabilityManager;
use SupportBay\Core\Http\RestResponse;
use SupportBay\Modules\Settings\Services\GeneralSettingsService;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class GeneralSettingsController {
  public function __construct(private readonly GeneralSettingsService $settings) {}

  public function registerRoutes(): void {
    register_rest_route('sbay/v1','/settings/general',['methods'=>'GET','callback'=>[$this,'show'],'permission_callback'=>[$this,'permissions']]);
    register_rest_route('sbay/v1','/settings/general',['methods'=>'PUT','callback'=>[$this,'update'],'permission_callback'=>[$this,'permissions']]);
  }

  public function permissions(): bool|WP_Error {
    return current_user_can(CapabilityManager::MANAGE_SETTINGS) ? true : new WP_Error('sbay_permission_denied','You are not allowed to manage settings.',['status'=>403]);
  }

  public function show(): WP_REST_Response { return RestResponse::success($this->settings->get(),'General settings retrieved.'); }

  public function update(WP_REST_Request $request): WP_REST_Response {
    $data=[];
    foreach (['registration_override','disable_registration_form','disable_guest_ticket_creation','client_user_default_role','support_portal_page_id','shortcode_mode','footer_copyright_text','remove_powered_by_branding','delete_data_on_uninstall','wordpress_auth_enabled','wordpress_login_url','wordpress_registration_url','wordpress_profile_enabled','sequential_track_id_enabled','sequential_track_id_prefix','sequential_track_id_length','ticket_list_auto_refresh_enabled','ticket_list_auto_refresh_interval','smart_need_reply_sorting_enabled','dashboard_logo_attachment_id','portal_logo_attachment_id','file_upload_enabled','file_upload_max_size_mb','file_upload_allowed_groups','attachment_popup_preview_enabled','ticket_status_labels','recaptcha_v3_enabled','recaptcha_v3_site_key','recaptcha_v3_secret_key','recaptcha_v3_show_login','recaptcha_v3_show_guest_ticket','recaptcha_v3_show_registration','recaptcha_v3_hide_badge','style_palette','custom_css','purchase_provider_field_label'] as $key) {
      if ($request->has_param($key)) { $data[$key]=$request->get_param($key); }
    }
    try {
      return RestResponse::success($this->settings->update($data),'General settings saved.');
    } catch (\InvalidArgumentException $exception) {
      return RestResponse::error($exception->getMessage(),'SETTINGS_INVALID',[],422);
    }
  }
}
