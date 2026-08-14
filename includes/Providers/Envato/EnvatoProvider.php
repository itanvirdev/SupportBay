<?php

declare(strict_types=1);

namespace SupportBay\Providers\Envato;

use RuntimeException;
use SupportBay\Core\Integrations\Contracts\IntegrationProvider;
use SupportBay\Core\Integrations\Contracts\OAuthProvider;
use SupportBay\Core\Integrations\Contracts\PurchaseVerificationProvider;
use SupportBay\Core\Integrations\Contracts\RefreshableOAuthProvider;
use SupportBay\Core\Integrations\Contracts\ConfigurableIntegrationProvider;
use SupportBay\Core\Integrations\Data\ProviderConfigurationField;
use SupportBay\Core\Integrations\Data\OAuthIdentityData;
use SupportBay\Core\Integrations\Data\OAuthLoginData;
use SupportBay\Core\Integrations\Data\OAuthTokenData;
use SupportBay\Core\Integrations\Data\PurchaseVerificationData;
use SupportBay\Modules\Providers\Enums\ProviderCategory;
use SupportBay\Providers\Envato\Services\EnvatoCustomerService;
use SupportBay\Providers\Envato\Services\EnvatoOAuthService;
use SupportBay\Providers\Envato\Services\EnvatoPurchaseService;

final class EnvatoProvider implements
  IntegrationProvider,
  ConfigurableIntegrationProvider,
  OAuthProvider,
  RefreshableOAuthProvider,
  PurchaseVerificationProvider {
  /**
   * Constructor.
   */
  public function __construct(
    private readonly EnvatoPurchaseService $purchases,
    private readonly EnvatoOAuthService $oauth,
    private readonly EnvatoCustomerService $customers,
  ) {
  }

  /**
   * Build the Envato authorization URL.
   *
   * @param array<string, mixed> $context
   */
  public function authorizationUrl(array $context): string {
    return $this->oauth->authorizationUrl(
      (string) ($context['client_id'] ?? ''),
      (string) ($context['redirect_uri'] ?? ''),
      (string) ($context['state'] ?? ''),
    );
  }

  /**
   * Exchange an OAuth code and normalize the Envato identity.
   *
   * @param array<string, mixed> $context
   */
  public function authenticateOAuth(
    string $code,
    array $context,
  ): OAuthLoginData {
    $token = $this->oauth->exchangeCode(
      (string) ($context['client_id'] ?? ''),
      (string) ($context['client_secret'] ?? ''),
      (string) ($context['redirect_uri'] ?? ''),
      $code,
    );

    $accessToken = sanitize_text_field(
      (string) ($token['access_token'] ?? '')
    );

    if ($accessToken === '') {
      throw new RuntimeException(
        'Envato did not return an access token.'
      );
    }

    $account = $this->customers->account($accessToken);
    $accountId = $this->customers->id($account);
    $username = $this->sanitizeNullable(
      $this->customers->username($account)
    );

    if ($accountId === null || $username === null) {
      throw new RuntimeException(
        'Envato did not return a valid customer identity.'
      );
    }

    return new OAuthLoginData(
      identity: new OAuthIdentityData(
        provider: $this->slug(),
        providerReference: (string) $accountId,
        username: $username,
        email: $this->sanitizeEmail(
          $this->customers->email($account)
        ),
        displayName: $username,
        avatarUrl: $this->sanitizeUrl(
          $this->customers->avatar($account)
        ),
        country: $this->sanitizeNullable(
          $this->customers->country($account)
        ),
        snapshot: $account,
      ),
      token: new OAuthTokenData(
        accessToken: $accessToken,
        refreshToken: $this->sanitizeNullable(
          isset($token['refresh_token'])
            ? (string) $token['refresh_token']
            : null
        ),
        tokenType: sanitize_text_field(
          (string) ($token['token_type'] ?? 'Bearer')
        ),
        expiresIn: isset($token['expires_in'])
          ? (int) $token['expires_in']
          : null,
      ),
    );
  }

  /**
   * Refresh and normalize an Envato OAuth token.
   *
   * @param array<string, mixed> $context
   */
  public function refreshOAuthToken(
    OAuthTokenData $token,
    array $context,
  ): OAuthTokenData {
    $refreshToken = trim((string) $token->refreshToken());
    $clientId = trim((string) ($context['client_id'] ?? ''));
    $clientSecret = trim((string) ($context['client_secret'] ?? ''));

    if ($refreshToken === '' || $clientId === '' || $clientSecret === '') {
      throw new RuntimeException(
        'Envato OAuth credentials are incomplete. Please reconnect the provider.'
      );
    }

    $response = $this->oauth->refreshToken(
      $clientId,
      $clientSecret,
      $refreshToken,
    );
    $accessToken = sanitize_text_field(
      (string) ($response['access_token'] ?? '')
    );

    if ($accessToken === '') {
      throw new RuntimeException(
        'Envato did not return a refreshed access token.'
      );
    }

    return new OAuthTokenData(
      accessToken: $accessToken,
      refreshToken: $this->sanitizeNullable(
        isset($response['refresh_token'])
          ? (string) $response['refresh_token']
          : $refreshToken
      ),
      tokenType: sanitize_text_field(
        (string) ($response['token_type'] ?? $token->tokenType())
      ),
      expiresIn: isset($response['expires_in'])
        ? max(0, (int) $response['expires_in'])
        : $token->expiresIn(),
    );
  }

  /**
   * Unique integration identifier.
   */
  public function slug(): string {
    return 'envato';
  }

  /**
   * Display name.
   */
  public function name(): string {
    return 'Envato';
  }

  /**
   * Integration category.
   */
  public function category(): ProviderCategory {
    return ProviderCategory::MARKETPLACE;
  }

  /**
   * Integration version.
   */
  public function version(): string {
    return '1.0.0';
  }

  /**
   * Boot the integration.
   */
  public function boot(): void {
    /**
     * Future responsibilities:
     *
     * - Register OAuth routes
     * - Register REST API endpoints
     * - Register AJAX actions
     * - Register webhooks
     * - Register scheduled sync jobs
     * - Register admin settings
     * - Register CLI commands
     */
  }

  /** @return ProviderConfigurationField[] */
  public function configurationFields(): array {
    return [
      new ProviderConfigurationField(
        key: 'client_id',
        label: 'Client ID',
        required: true,
        description: 'The client identifier from your Envato OAuth application.',
      ),
      new ProviderConfigurationField(
        key: 'client_secret',
        label: 'Client Secret',
        type: 'secret',
        required: true,
        description: 'Stored encrypted. Leave blank when editing to keep the existing secret.',
      ),
      new ProviderConfigurationField(
        key: 'redirect_uri',
        label: 'Redirect URI',
        type: 'url',
        required: true,
        description: 'Must exactly match the callback URL registered with Envato.',
      ),
    ];
  }

  /**
   * Verify and normalize an Envato purchase.
   *
   * @param array<string, mixed> $context
   */
  public function verifyPurchase(
    string $reference,
    array $context = [],
  ): PurchaseVerificationData {
    $accessToken = trim((string) ($context['access_token'] ?? ''));

    if ($accessToken === '') {
      throw new RuntimeException(
        'An Envato access token is required to verify a purchase.'
      );
    }

    $purchase = $this->purchases->verify(
      $accessToken,
      trim($reference),
    );

    $productId = $this->purchases->productId($purchase);
    $snapshot = $purchase;

    unset($snapshot['code']);

    return new PurchaseVerificationData(
      provider: $this->slug(),
      providerReference: trim($reference),
      providerCustomerReference: $this->sanitizeNullable(
        $this->purchases->buyer($purchase)
      ),
      productId: $productId !== null ? (string) $productId : null,
      productName: $this->sanitizeNullable(
        $this->purchases->productName($purchase)
      ),
      licenseType: $this->sanitizeNullable(
        $this->purchases->license($purchase)
      ),
      supportExpiresAt: $this->sanitizeNullable(
        $this->purchases->supportExpiry($purchase)
      ),
      purchasedAt: $this->sanitizeNullable(
        $this->purchases->purchasedAt($purchase)
      ),
      status: 'verified',
      snapshot: $snapshot,
    );
  }

  /**
   * Sanitize an optional provider value.
   */
  private function sanitizeNullable(?string $value): ?string {
    if ($value === null) {
      return null;
    }

    $value = sanitize_text_field($value);

    return $value !== '' ? $value : null;
  }

  private function sanitizeEmail(?string $value): ?string {
    if ($value === null) {
      return null;
    }

    $value = sanitize_email($value);

    return $value !== '' ? $value : null;
  }

  private function sanitizeUrl(?string $value): ?string {
    if ($value === null) {
      return null;
    }

    $value = esc_url_raw($value);

    return $value !== '' ? $value : null;
  }
}
