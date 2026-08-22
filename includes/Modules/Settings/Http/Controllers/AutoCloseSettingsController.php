<?php
declare(strict_types=1);
namespace SupportBay\Modules\Settings\Http\Controllers;
use SupportBay\Core\Authorization\CapabilityManager;use SupportBay\Core\Http\RestResponse;use SupportBay\Modules\Settings\Services\AutoCloseSettingsService;use WP_Error;use WP_REST_Request;use WP_REST_Response;
final class AutoCloseSettingsController{
  public function __construct(private readonly AutoCloseSettingsService $settings){}
  public function registerRoutes():void{register_rest_route('sbay/v1','/settings/auto-close',['methods'=>'GET','callback'=>[$this,'show'],'permission_callback'=>[$this,'permissions']]);register_rest_route('sbay/v1','/settings/auto-close',['methods'=>'PUT','callback'=>[$this,'update'],'permission_callback'=>[$this,'permissions']]);}
  public function permissions():bool|WP_Error{return current_user_can(CapabilityManager::MANAGE_SETTINGS)?true:new WP_Error('sbay_permission_denied','You are not allowed to manage settings.',['status'=>403]);}
  public function show():WP_REST_Response{return RestResponse::success($this->settings->get(),'Auto-close settings retrieved.');}
  public function update(WP_REST_Request $request):WP_REST_Response{try{return RestResponse::success($this->settings->update((array)$request->get_json_params()),'Auto-close settings saved.');}catch(\InvalidArgumentException $exception){return RestResponse::error($exception->getMessage(),'SETTINGS_INVALID',[],422);}}
}
