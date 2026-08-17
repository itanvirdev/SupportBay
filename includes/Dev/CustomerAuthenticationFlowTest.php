<?php

declare(strict_types=1);

namespace SupportBay\Dev;

use SupportBay\Core\Testing\Assert;
use SupportBay\Core\Testing\FlowTest;
use SupportBay\Modules\Auth\Http\CustomerAuthController;
use SupportBay\Modules\Customers\Services\CustomerService;
use SupportBay\Modules\Settings\Services\GeneralSettingsService;
use WP_REST_Request;

final class CustomerAuthenticationFlowTest extends FlowTest {
  protected static function title(): string { return 'Customer Authentication Flow Test'; }

  protected static function execute(...$services): void {
    /** @var CustomerAuthController $auth */
    /** @var CustomerService $customers */
    /** @var GeneralSettingsService $settings */
    [$auth,$customers,$settings] = $services;
    if (did_action('rest_api_init') === 0) { do_action('rest_api_init', rest_get_server()); }
    Assert::true(isset(rest_get_server()->get_routes()['/sbay/v1/auth/login']), 'Public customer authentication routes are registered.');
    $previousRegistration = get_option('users_can_register');
    $previousSettings = $settings->get();
    update_option('users_can_register', 1);
    $settings->update(['disable_registration_form'=>false]);
    wp_set_current_user(0);
    $suffix = strtolower(wp_generate_password(8, false, false));
    $username = 'sbay-auth-' . $suffix;
    $email = $username . '@example.test';
    $password = 'Safe-password-42';
    $userId = 0;
    $customerId = 0;

    try {
      $register = new WP_REST_Request('POST', '/sbay/v1/auth/register');
      $register->set_header('X-WP-Nonce', wp_create_nonce('wp_rest'));
      $register->set_body_params(['first_name'=>'Portal','last_name'=>'Customer','email'=>$email,'password'=>$password,'password_confirmation'=>$password]);
      $response = $auth->register($register);
      $user = get_user_by('email', $email);
      $userId = $user ? (int)$user->ID : 0;
      Assert::true($response->get_status() === 201 && $userId > 0 && in_array($settings->clientUserDefaultRole(), $user->roles, true), 'Registration assigns the administrator-selected WordPress client role.');
      $customerId = $customers->findByUser($userId)?->id() ?? 0;
      Assert::true($customerId > 0, 'Registration links the Subscriber to a SupportBay customer record.');
      $auth->logout();
      wp_set_current_user(0);
      $login = new WP_REST_Request('POST', '/sbay/v1/auth/login');
      $login->set_header('X-WP-Nonce', wp_create_nonce('wp_rest'));
      $login->set_body_params(['login'=>$email,'password'=>$password,'remember'=>true]);
      Assert::true($auth->login($login)->get_status() === 200 && get_current_user_id() === $userId, 'Subscribers can log in using their email address and WordPress password.');
    } finally {
      wp_set_current_user(1);
      if ($customerId > 0) { $customers->deleteWithUser($customerId); }
      update_option('users_can_register', $previousRegistration);
      $settings->update(['disable_registration_form'=>$previousSettings['disable_registration_form']]);
      wp_set_current_user(0);
    }
  }
}
