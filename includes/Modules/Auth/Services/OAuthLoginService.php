<?php

declare(strict_types=1);

namespace SupportBay\Modules\Auth\Services;

use RuntimeException;
use SupportBay\Core\Integrations\Contracts\OAuthProvider;
use SupportBay\Core\Integrations\IntegrationManager;
use SupportBay\Modules\Customers\Entities\Customer;
use SupportBay\Modules\Customers\Services\CustomerService;

final class OAuthLoginService {
  public function __construct(
    private readonly IntegrationManager $integrations,
    private readonly CustomerService $customers,
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

    return $this->customers->linkProvider($login);
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
}
