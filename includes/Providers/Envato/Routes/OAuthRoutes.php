<?php

declare(strict_types=1);

namespace SupportBay\Providers\Envato\Routes;

use SupportBay\Modules\Providers\Services\ProviderConfiguration;
use SupportBay\Providers\Envato\Services\EnvatoOAuthService;

final class OAuthRoutes {
  /**
   * Provider slug.
   */
  private const PROVIDER = 'envato';

  /**
   * Constructor.
   */
  public function __construct(
    private readonly EnvatoOAuthService $oauth,
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
      $this->config->clientId(self::PROVIDER) ?? '',
      $this->config->redirectUri(self::PROVIDER) ?? '',
      wp_create_nonce('supportbay-envato')
    );

    wp_safe_redirect($url);

    exit;
  }

  /**
   * OAuth callback.
   *
   * Customer linking and authentication will be implemented
   * in a later phase.
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

    /**
     * Future flow:
     *
     * exchangeCode()
     * ↓
     * account()
     * ↓
     * CustomerService
     * ↓
     * AuthService
     * ↓
     * Redirect
     */

    wp_die(
      esc_html__(
        'Envato OAuth callback received successfully.',
        'supportbay'
      )
    );
  }
}
