<?php

declare(strict_types=1);

namespace SupportBay\Modules\Auth\Http;

use RuntimeException;
use SupportBay\Core\Integrations\Contracts\OAuthProvider;
use SupportBay\Core\Integrations\IntegrationManager;
use SupportBay\Modules\Auth\Services\OAuthLoginService;
use SupportBay\Modules\Customers\Services\CustomerService;
use SupportBay\Modules\Providers\Services\ProviderConfiguration;
use SupportBay\Modules\Providers\Services\ProviderService;
use SupportBay\Modules\Settings\Services\GeneralSettingsService;

final class OAuthRoutes {
  private const ACTION = 'sbay_oauth';

  private const ENVATO_VERIFIER_COOKIE = 'sbay_envato_oauth_verifier';

  private const ENVATO_VERIFIER_TTL = 600;

  public function __construct(
    private readonly OAuthLoginService $oauth,
    private readonly CustomerService $customers,
    private readonly IntegrationManager $integrations,
    private readonly ProviderService $providers,
    private readonly ProviderConfiguration $configuration,
    private readonly GeneralSettingsService $settings,
  ) {
  }

  public function register(): void {
    add_action('init', [$this, 'handle']);
  }

  public function connectUrl(string $provider): string {
    return add_query_arg([
      self::ACTION => 'login',
      'provider' => sanitize_key($provider),
    ], home_url('/'));
  }

  public function handle(): void {
    $envatoCallback = absint($_GET['sbayenvato'] ?? 0) === 1;
    $action = sanitize_key(
      wp_unslash((string) ($_GET[self::ACTION] ?? ''))
    );

    if ($envatoCallback) {
      $action = 'callback';
    }

    if (! in_array($action, ['login', 'callback'], true)) {
      return;
    }

    $provider = sanitize_key(
      wp_unslash((string) ($_GET['provider'] ?? ''))
    );
    if ($envatoCallback) {
      $provider = 'envato';
    }

    if ($action === 'callback' && isset($_GET['error'])) {
      $this->redirectAfterCancellation();
    }

    try {
      $this->assertAvailable($provider);

      if ($action === 'login') {
        $this->redirectToProvider($provider);
      }

      $this->completeCallback($provider);
    } catch (RuntimeException $exception) {
      wp_die(
        esc_html($this->failureMessage($provider, $exception)),
        esc_html__('Provider connection failed', 'supportbay'),
        ['response' => 400],
      );
    }
  }

  private function failureMessage(
    string $provider,
    RuntimeException $exception,
  ): string {
    $message = sanitize_text_field($exception->getMessage());

    if (
      $provider === 'envato'
      && str_contains(strtolower($message), 'client authentication failed')
    ) {
      return 'Envato rejected the OAuth application credentials. In Envato My Apps, copy the OAuth Client ID and Secret Application Key (not a Personal Token) from the same app, save both again in SupportBay, and ensure the HTTPS Confirmation URL matches exactly. Then return to the portal and start a new Login with Envato request; do not refresh this callback page because its authorization code is single-use.';
    }

    return $message !== ''
      ? $message
      : 'The provider connection could not be completed.';
  }

  private function redirectToProvider(string $provider): never {
    $context = $this->configuration->all($provider);

    if ($provider === 'envato') {
      $this->storeEnvatoVerifier();
    } else {
      $context['state'] = wp_create_nonce($this->nonceAction($provider));
    }

    $url = $this->oauth->authorizationUrl(
      $provider,
      $context,
    );

    wp_redirect($url);
    exit;
  }

  private function redirectAfterCancellation(): never {
    wp_safe_redirect($this->settings->portalUrl());
    exit;
  }

  private function completeCallback(string $provider): never {
    $state = sanitize_text_field(
      wp_unslash((string) ($_GET['state'] ?? ''))
    );

    if (
      ($provider === 'envato' && ! $this->verifyEnvatoCallback($state))
      || ($provider !== 'envato' && ! wp_verify_nonce($state, $this->nonceAction($provider)))
    ) {
      throw new RuntimeException('The OAuth connection request has expired.');
    }

    $code = sanitize_text_field(
      wp_unslash((string) ($_GET['code'] ?? ''))
    );
    $context = $this->configuration->all($provider);
    $customer = is_user_logged_in()
      ? $this->customers->findByUser(get_current_user_id())
      : null;

    if ($customer) {
      $this->oauth->connect(
        $customer->id(),
        $provider,
        $code,
        $context,
      );
    } else {
      $customer = $this->oauth->login(
        $provider,
        $code,
        $context,
      );
      wp_set_auth_cookie($customer->userId(), true);
    }

    $storedProvider = $this->providers->findBySlug($provider);

    if ($storedProvider) {
      $this->providers->connected($storedProvider->id());
    }

    wp_safe_redirect(add_query_arg(
      'provider_connected',
      $provider,
      trailingslashit($this->settings->portalUrl()) . 'profile/',
    ));
    exit;
  }

  private function assertAvailable(string $provider): void {
    $storedProvider = $this->providers->findBySlug($provider);

    if (
      $provider === '' ||
      ! $storedProvider ||
      ! $storedProvider->isEnabled() ||
      ! $this->configuration->configured($provider, 'oauth') ||
      ! filter_var($this->configuration->get($provider, 'oauth_login_enabled', false), FILTER_VALIDATE_BOOL) ||
      ! $this->integrations->has($provider) ||
      ! ($this->integrations->integration($provider) instanceof OAuthProvider)
    ) {
      throw new RuntimeException('This OAuth provider is not available.');
    }
  }

  private function nonceAction(string $provider): string {
    return 'supportbay-oauth-' . sanitize_key($provider);
  }

  private function storeEnvatoVerifier(): void {
    $verifier = wp_create_nonce($this->nonceAction('envato'));

    setcookie(self::ENVATO_VERIFIER_COOKIE, $verifier, [
      'expires' => time() + self::ENVATO_VERIFIER_TTL,
      'path' => defined('COOKIEPATH') && COOKIEPATH !== '' ? COOKIEPATH : '/',
      'domain' => defined('COOKIE_DOMAIN') ? COOKIE_DOMAIN : '',
      'secure' => is_ssl(),
      'httponly' => true,
      'samesite' => 'Lax',
    ]);
    $_COOKIE[self::ENVATO_VERIFIER_COOKIE] = $verifier;
  }

  private function verifyEnvatoCallback(string $state): bool {
    if ($state !== '') {
      return wp_verify_nonce($state, $this->nonceAction('envato')) !== false;
    }

    $verifier = sanitize_text_field(
      wp_unslash((string) ($_COOKIE[self::ENVATO_VERIFIER_COOKIE] ?? ''))
    );
    $this->clearEnvatoVerifier();

    return $verifier !== ''
      && wp_verify_nonce($verifier, $this->nonceAction('envato')) !== false;
  }

  private function clearEnvatoVerifier(): void {
    setcookie(self::ENVATO_VERIFIER_COOKIE, '', [
      'expires' => time() - HOUR_IN_SECONDS,
      'path' => defined('COOKIEPATH') && COOKIEPATH !== '' ? COOKIEPATH : '/',
      'domain' => defined('COOKIE_DOMAIN') ? COOKIE_DOMAIN : '',
      'secure' => is_ssl(),
      'httponly' => true,
      'samesite' => 'Lax',
    ]);
    unset($_COOKIE[self::ENVATO_VERIFIER_COOKIE]);
  }
}
