<?php

declare(strict_types=1);

namespace SupportBay\Providers\Envato\Routes;

use SupportBay\Modules\Providers\Services\ProviderConfiguration;
use SupportBay\Modules\Auth\Services\OAuthLoginService;

final class OAuthRoutes {
  /**
   * Provider slug.
   */
  private const PROVIDER = 'envato';

  /**
   * Constructor.
   */
  public function __construct(
    private readonly OAuthLoginService $oauth,
    private readonly ProviderConfiguration $config,
  ) {
  }

  /**
   * Register OAuth routes.
   */
  public function register(): void {
    add_action(
      'init',
      [$this, 'handle']
    );
  }

  /**
   * Handle incoming OAuth requests.
   */
  public function handle(): void {

    $action = sanitize_text_field(
      $_GET['sbay_envato'] ?? ''
    );

    if ($action === '') {
      return;
    }

    switch ($action) {

      case 'login':
        $this->login();
        break;

      case 'callback':
        $this->callback();
        break;
    }
  }

  /**
   * Redirect customer to Envato OAuth.
   */
  private function login(): void {

    $url = $this->oauth->authorizationUrl(
      self::PROVIDER,
      [
        'client_id'    => $this->config->clientId(self::PROVIDER) ?? '',
        'redirect_uri' => $this->config->redirectUri(self::PROVIDER) ?? '',
        'state'        => wp_create_nonce('supportbay-envato'),
      ]
    );

    wp_safe_redirect($url);

    exit;
  }

  /**
   * Authenticate the customer after the OAuth callback.
   */
  private function callback(): void {

    $code = sanitize_text_field(
      $_GET['code'] ?? ''
    );

    if ($code === '') {
      wp_die(
        esc_html__('Missing OAuth authorization code.', 'supportbay')
      );
    }

    $state = sanitize_text_field(
      $_GET['state'] ?? ''
    );

    if (! wp_verify_nonce($state, 'supportbay-envato')) {
      wp_die(
        esc_html__('Invalid OAuth state.', 'supportbay')
      );
    }

    $customer = $this->oauth->login(
      self::PROVIDER,
      $code,
      [
        'client_id'     => $this->config->clientId(self::PROVIDER) ?? '',
        'client_secret' => $this->config->clientSecret(self::PROVIDER) ?? '',
        'redirect_uri'  => $this->config->redirectUri(self::PROVIDER) ?? '',
      ]
    );

    wp_set_auth_cookie($customer->userId(), true);

    wp_safe_redirect(home_url('/support/'));

    exit;
  }
}
