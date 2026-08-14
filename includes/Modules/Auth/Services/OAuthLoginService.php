<?php

declare(strict_types=1);

namespace SupportBay\Modules\Auth\Services;

use RuntimeException;
use SupportBay\Core\Integrations\Contracts\OAuthProvider;
use SupportBay\Core\Integrations\Contracts\RefreshableOAuthProvider;
use SupportBay\Core\Integrations\Data\OAuthTokenData;
use SupportBay\Core\Integrations\Data\OAuthLoginData;
use SupportBay\Core\Integrations\IntegrationManager;
use SupportBay\Modules\Customers\Entities\Customer;
use SupportBay\Modules\Customers\Services\CustomerService;
use SupportBay\Modules\Providers\Services\ProviderConfiguration;

final class OAuthLoginService {
  public function __construct(
    private readonly IntegrationManager $integrations,
    private readonly CustomerService $customers,
    private readonly ProviderConfiguration $configuration,
  ) {
  }

  /**
   * Build a provider authorization URL.
   *
   * @param array<string, mixed> $context
   */
  public function authorizationUrl(
    string $provider,
    array $context,
  ): string {
    return $this->provider($provider)->authorizationUrl($context);
  }

  /**
   * Authenticate and link a normalized provider identity.
   *
   * @param array<string, mixed> $context
   */
  public function login(
    string $provider,
    string $code,
    array $context = [],
  ): Customer {
    $login = $this->authenticate($provider, $code, $context);

    return $this->customers->linkProvider($login);
  }

  /**
   * Authenticate and attach a provider to an existing customer.
   *
   * @param array<string, mixed> $context
   */
  public function connect(
    int $customerId,
    string $provider,
    string $code,
    array $context = [],
  ): Customer {
    return $this->customers->connectProvider(
      $customerId,
      $this->authenticate($provider, $code, $context),
    );
  }

  /**
   * Resolve a usable provider access context for a customer.
   *
   * @return array<string, mixed>
   */
  public function providerContext(
    int $customerId,
    string $provider,
  ): array {
    $provider = sanitize_key($provider);
    $integration = $this->integrations->integration($provider);

    if (! $integration instanceof OAuthProvider) {
      return [];
    }

    $context = $this->customers->providerContext(
      $customerId,
      $provider,
    );

    if (trim((string) ($context['access_token'] ?? '')) === '') {
      throw new RuntimeException(
        'The provider connection must be reconnected.'
      );
    }

    if (! $this->isExpiring($context)) {
      return $context;
    }

    if (
      ! $integration instanceof RefreshableOAuthProvider ||
      trim((string) ($context['refresh_token'] ?? '')) === ''
    ) {
      throw new RuntimeException(
        'The provider connection has expired and must be reconnected.'
      );
    }

    $token = $integration->refreshOAuthToken(
      OAuthTokenData::fromArray($context),
      $this->providerConfiguration($provider),
    );

    if ($token->accessToken() === '') {
      throw new RuntimeException(
        'The provider connection could not be refreshed. Please reconnect it.'
      );
    }

    $this->customers->updateProviderToken(
      $customerId,
      $provider,
      $token,
    );

    return $this->customers->providerContext(
      $customerId,
      $provider,
    );
  }

  private function provider(string $provider): OAuthProvider {
    $integration = $this->integrations->integration(
      sanitize_key($provider)
    );

    if (! $integration instanceof OAuthProvider) {
      throw new RuntimeException(
        sprintf(
          'Integration "%s" does not support OAuth login.',
          $provider
        )
      );
    }

    return $integration;
  }

  /**
   * Exchange an OAuth callback code for normalized login data.
   *
   * @param array<string, mixed> $context
   */
  private function authenticate(
    string $provider,
    string $code,
    array $context,
  ): OAuthLoginData {
    $provider = sanitize_key($provider);
    $code = trim($code);

    if ($code === '') {
      throw new RuntimeException(
        'OAuth authorization code is required.'
      );
    }

    $login = $this->provider($provider)->authenticateOAuth(
      $code,
      $context,
    );

    if ($login->identity()->provider() !== $provider) {
      throw new RuntimeException(
        'OAuth identity does not match the requested provider.'
      );
    }

    return $login;
  }

  /** @param array<string, mixed> $context */
  private function isExpiring(array $context): bool {
    if (! isset($context['expires_at'])) {
      return false;
    }

    return (int) $context['expires_at'] <=
      ((int) current_time('timestamp') + 60);
  }

  /** @return array<string, mixed> */
  private function providerConfiguration(string $provider): array {
    try {
      return $this->configuration->all($provider);
    } catch (RuntimeException) {
      return [];
    }
  }
}
