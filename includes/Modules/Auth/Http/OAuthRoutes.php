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

final class OAuthRoutes {
  private const ACTION = 'sbay_oauth';

  public function __construct(
    private readonly OAuthLoginService $oauth,
    private readonly CustomerService $customers,
    private readonly IntegrationManager $integrations,
    private readonly ProviderService $providers,
    private readonly ProviderConfiguration $configuration,
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
    $action = sanitize_key(
      wp_unslash((string) ($_GET[self::ACTION] ?? ''))
    );

    if (! in_array($action, ['login', 'callback'], true)) {
      return;
    }

    $provider = sanitize_key(
      wp_unslash((string) ($_GET['provider'] ?? ''))
    );

    try {
      $this->assertAvailable($provider);

      if ($action === 'login') {
        $this->redirectToProvider($provider);
      }

      $this->completeCallback($provider);
    } catch (RuntimeException $exception) {
      wp_die(
        esc_html($exception->getMessage()),
        esc_html__('Provider connection failed', 'supportbay'),
        ['response' => 400],
      );
    }
  }

  private function redirectToProvider(string $provider): never {
    $url = $this->oauth->authorizationUrl(
      $provider,
      array_merge($this->configuration->all($provider), [
        'state' => wp_create_nonce($this->nonceAction($provider)),
      ]),
    );

    wp_redirect($url);
    exit;
  }

  private function completeCallback(string $provider): never {
    $state = sanitize_text_field(
      wp_unslash((string) ($_GET['state'] ?? ''))
    );

    if (! wp_verify_nonce($state, $this->nonceAction($provider))) {
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

    wp_safe_redirect(home_url('/support/profile/?provider_connected=' . $provider));
    exit;
  }

  private function assertAvailable(string $provider): void {
    $storedProvider = $this->providers->findBySlug($provider);

    if (
      $provider === '' ||
      ! $storedProvider ||
      ! $storedProvider->isEnabled() ||
      ! $this->configuration->configured($provider) ||
      ! $this->integrations->has($provider) ||
      ! ($this->integrations->integration($provider) instanceof OAuthProvider)
    ) {
      throw new RuntimeException('This OAuth provider is not available.');
    }
  }

  private function nonceAction(string $provider): string {
    return 'supportbay-oauth-' . sanitize_key($provider);
  }
}
