<?php

declare(strict_types=1);

namespace SupportBay\Dev;

use SupportBay\Core\Integrations\Contracts\IntegrationProvider;
use SupportBay\Core\Integrations\Contracts\PurchaseVerificationProvider;
use SupportBay\Core\Integrations\Data\PurchaseVerificationData;
use SupportBay\Modules\Providers\Enums\ProviderCategory;

final class FakePurchaseProvider implements
  IntegrationProvider,
  PurchaseVerificationProvider {
  /**
   * Unique integration identifier.
   */
  public function slug(): string {
    return 'fake-purchase';
  }

  /**
   * Display name.
   */
  public function name(): string {
    return 'Fake Purchase Provider';
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
  }

  /**
   * Return deterministic normalized purchase data.
   *
   * @param array<string, mixed> $context
   */
  public function verifyPurchase(
    string $reference,
    array $context = [],
  ): PurchaseVerificationData {
    return new PurchaseVerificationData(
      provider: $this->slug(),
      providerReference: $reference,
      providerCustomerReference: 'test-buyer',
      productId: '12345678',
      productName: 'SupportBay Test Product',
      licenseType: 'Regular License',
      supportExpiresAt: '2030-01-01 00:00:00',
      purchasedAt: '2026-01-01 00:00:00',
      status: 'verified',
      snapshot: [
        'reference' => $reference,
        'source'    => 'fake',
      ],
    );
  }
}
