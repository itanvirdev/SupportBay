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
    foreach (['registration_override','disable_registration_form','disable_guest_ticket_creation','client_user_default_role','support_portal_page_id','shortcode_mode'] as $key) {
      if ($request->has_param($key)) { $data[$key]=$request->get_param($key); }
    }
    return RestResponse::success($this->settings->update($data),'General settings saved.');
  }
}
