<?php

declare(strict_types=1);

namespace SupportBay\Modules\Auth\Http;

use SupportBay\Core\Http\RestResponse;
use SupportBay\Modules\Customers\Enums\CustomerSource;
use SupportBay\Modules\Customers\Services\CustomerService;
use SupportBay\Modules\Settings\Services\GeneralSettingsService;
use SupportBay\Modules\Settings\Services\RecaptchaService;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class CustomerAuthController {
  public function __construct(
    private readonly CustomerService $customers,
    private readonly GeneralSettingsService $settings,
    private readonly RecaptchaService $recaptcha,
  ) {}

  public function registerRoutes(): void {
    register_rest_route('sbay/v1', '/auth/session', ['methods'=>'GET','callback'=>[$this,'session'],'permission_callback'=>[$this,'publicPermission']]);
    register_rest_route('sbay/v1', '/auth/login', ['methods'=>'POST','callback'=>[$this,'login'],'permission_callback'=>[$this,'publicPermission']]);
    register_rest_route('sbay/v1', '/auth/register', ['methods'=>'POST','callback'=>[$this,'register'],'permission_callback'=>[$this,'publicPermission']]);
    register_rest_route('sbay/v1', '/auth/logout', ['methods'=>'POST','callback'=>[$this,'logout'],'permission_callback'=>[$this,'authenticatedPermission']]);
  }

  public function publicPermission(WP_REST_Request $request): bool|WP_Error {
    $nonce = sanitize_text_field((string) $request->get_header('X-WP-Nonce'));
    return wp_verify_nonce($nonce, 'wp_rest')
      ? true
      : new WP_Error('sbay_invalid_nonce', 'The authentication request has expired.', ['status'=>403]);
  }

  public function authenticatedPermission(): bool|WP_Error {
    return is_user_logged_in() ? true : new WP_Error('sbay_authentication_required', 'Authentication is required.', ['status'=>401]);
  }

  public function session(): WP_REST_Response {
    $user = wp_get_current_user();
    return RestResponse::success([
      'authenticated' => $user->exists(),
      'registration_enabled' => $this->settings->registrationEnabled(),
      'user' => $user->exists() ? ['id'=>(int)$user->ID,'name'=>sanitize_text_field($user->display_name)] : null,
    ], 'Authentication session retrieved.');
  }

  public function login(WP_REST_Request $request): WP_REST_Response {
    try { $this->recaptcha->verify((string)$request->get_param('recaptcha_token'),'login',isset($_SERVER['REMOTE_ADDR'])?sanitize_text_field((string)$_SERVER['REMOTE_ADDR']):null); }
    catch (\RuntimeException $exception) { return RestResponse::error($exception->getMessage(),'RECAPTCHA_FAILED',[],403); }
    $login = sanitize_text_field(wp_unslash((string) $request->get_param('login')));
    $password = (string) $request->get_param('password');
    if ($login === '' || $password === '') { return RestResponse::error('Username/email and password are required.', 'LOGIN_REQUIRED', [], 422); }
    $user = wp_signon(['user_login'=>$login,'user_password'=>$password,'remember'=>(bool)$request->get_param('remember')], is_ssl());
    if ($user instanceof WP_Error) { return RestResponse::error('The username/email or password is incorrect.', 'LOGIN_FAILED', [], 401); }
    wp_set_current_user($user->ID);
    $this->customers->ensureWordPressCustomer((int)$user->ID);
    return RestResponse::success(['redirect'=>$this->settings->portalUrl()], 'Login successful.');
  }

  public function register(WP_REST_Request $request): WP_REST_Response {
    if (! $this->settings->registrationEnabled()) { return RestResponse::error('Registration is currently disabled.', 'REGISTRATION_DISABLED', [], 403); }
    try { $this->recaptcha->verify((string)$request->get_param('recaptcha_token'),'registration',isset($_SERVER['REMOTE_ADDR'])?sanitize_text_field((string)$_SERVER['REMOTE_ADDR']):null); }
    catch (\RuntimeException $exception) { return RestResponse::error($exception->getMessage(),'RECAPTCHA_FAILED',[],403); }
    $email = sanitize_email(wp_unslash((string)$request->get_param('email')));
    $password = (string)$request->get_param('password');
    $confirmation = (string)$request->get_param('password_confirmation');
    $firstName = sanitize_text_field(wp_unslash((string)$request->get_param('first_name')));
    $lastName = sanitize_text_field(wp_unslash((string)$request->get_param('last_name')));
    if ($firstName === '' || $lastName === '' || ! is_email($email) || strlen($password) < 8) { return RestResponse::error('Enter your first name, last name, valid email, and password of at least 8 characters.', 'REGISTRATION_INVALID', [], 422); }
    if (! hash_equals($password, $confirmation)) { return RestResponse::error('Passwords do not match.', 'REGISTRATION_PASSWORD_MISMATCH', [], 422); }
    if (email_exists($email)) { return RestResponse::error('That email is already registered.', 'REGISTRATION_EXISTS', [], 409); }
    $username = $this->uniqueUsername($email);
    $userId = wp_insert_user(['user_login'=>$username,'user_email'=>$email,'user_pass'=>$password,'first_name'=>$firstName,'last_name'=>$lastName,'display_name'=>trim($firstName.' '.$lastName),'role'=>$this->settings->clientUserDefaultRole()]);
    if ($userId instanceof WP_Error) { return RestResponse::error($userId->get_error_message(), 'REGISTRATION_FAILED', [], 422); }
    $this->customers->ensureWordPressCustomer((int)$userId, CustomerSource::REGISTRATION);
    wp_set_current_user((int)$userId);
    wp_set_auth_cookie((int)$userId, true, is_ssl());
    return RestResponse::success(['redirect'=>$this->settings->portalUrl()], 'Registration successful.', [], 201);
  }

  public function logout(): WP_REST_Response {
    wp_logout();
    return RestResponse::success(['redirect'=>trailingslashit($this->settings->portalUrl()).'login/'], 'Logout successful.');
  }

  private function uniqueUsername(string $email): string {
    $base = sanitize_user((string)strstr($email, '@', true), true) ?: 'customer';
    $candidate = $base;
    $suffix = 1;
    while (username_exists($candidate)) { $candidate = $base . '-' . $suffix++; }
    return $candidate;
  }
}
