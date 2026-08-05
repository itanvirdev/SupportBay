<?php

declare(strict_types=1);

namespace SupportBay\Providers\Envato;

use RuntimeException;
use SupportBay\Core\Integrations\Contracts\IntegrationProvider;
use SupportBay\Core\Integrations\Contracts\PurchaseVerificationProvider;
use SupportBay\Core\Integrations\Data\PurchaseVerificationData;
use SupportBay\Modules\Providers\Enums\ProviderCategory;
use SupportBay\Providers\Envato\Services\EnvatoPurchaseService;

final class EnvatoProvider implements
  IntegrationProvider,
  PurchaseVerificationProvider {
  /**
   * Constructor.
   */
  public function __construct(
    private readonly EnvatoPurchaseService $purchases,
  ) {
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
}
