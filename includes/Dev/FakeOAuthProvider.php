<?php

declare(strict_types=1);

namespace SupportBay\Dev;

use SupportBay\Core\Integrations\Contracts\IntegrationProvider;
use SupportBay\Core\Integrations\Contracts\OAuthProvider;
use SupportBay\Core\Integrations\Data\OAuthIdentityData;
use SupportBay\Core\Integrations\Data\OAuthLoginData;
use SupportBay\Core\Integrations\Data\OAuthTokenData;
use SupportBay\Modules\Providers\Enums\ProviderCategory;

final class FakeOAuthProvider implements
  IntegrationProvider,
  OAuthProvider {
  public function slug(): string {
    return 'fake-oauth';
  }

  public function name(): string {
    return 'Fake OAuth Provider';
  }

  public function category(): ProviderCategory {
    return ProviderCategory::MARKETPLACE;
  }

  public function version(): string {
    return '1.0.0';
  }

  public function boot(): void {
  }

  /**
   * @param array<string, mixed> $context
   */
  public function authorizationUrl(array $context): string {
    return 'https://example.test/oauth?state=' . rawurlencode(
      (string) ($context['state'] ?? '')
    );
  }

  /**
   * @param array<string, mixed> $context
   */
  public function authenticateOAuth(
    string $code,
    array $context,
  ): OAuthLoginData {
    $reference = (string) ($context['reference'] ?? $code);

    return new OAuthLoginData(
      identity: new OAuthIdentityData(
        provider: $this->slug(),
        providerReference: $reference,
        username: 'oauth-' . strtolower($reference),
        displayName: 'OAuth Test Customer',
        country: 'BD',
        snapshot: ['reference' => $reference],
      ),
      token: new OAuthTokenData(
        accessToken: 'fake-access-token',
        refreshToken: 'fake-refresh-token',
        expiresIn: 3600,
      ),
    );
  }
}
