<?php

declare(strict_types=1);

namespace SupportBay\Dev;

use SupportBay\Core\Integrations\IntegrationManager;
use SupportBay\Core\Testing\Assert;
use SupportBay\Core\Testing\FlowTest;
use SupportBay\Modules\Verifications\Enums\VerificationStatus;
use SupportBay\Modules\Verifications\Services\VerificationService;

final class ProviderVerificationFlowTest extends FlowTest {
  /**
   * Test title.
   */
  protected static function title(): string {
    return 'Provider Verification Flow Test';
  }

  /**
   * Execute flow.
   */
  protected static function execute(...$services): void {
    /** @var VerificationService $verifications */
    /** @var IntegrationManager $integrations */
    [$verifications, $integrations] = $services;

    $provider = new FakePurchaseProvider();

    if ($integrations->has($provider->slug())) {
      $integrations->unregister($provider->slug());
    }

    $integrations->register($provider);

    $reference = 'FAKE-' . strtoupper(
      wp_generate_password(16, false, false)
    );

    $verification = $verifications->verifyPurchase(
      provider: $provider->slug(),
      reference: $reference,
      customerId: 1,
    );

    Assert::equals(
      VerificationStatus::VERIFIED,
      $verification->status(),
      'Provider result creates a verified record.'
    );

    Assert::equals(
      'SupportBay Test Product',
      $verification->productName(),
      'Normalized product data is persisted.'
    );

    Assert::equals(
      1,
      $verification->customerId(),
      'Customer is linked to the verification.'
    );

    Assert::true(
      $verification->hasSnapshot(),
      'Provider snapshot is persisted.'
    );

    $duplicate = $verifications->verifyPurchase(
      provider: $provider->slug(),
      reference: $reference,
      customerId: 1,
    );

    Assert::equals(
      $verification->id(),
      $duplicate->id(),
      'Repeated verification returns the existing record.'
    );

    $persisted = $verifications->findByReference(
      $provider->slug(),
      $reference,
    );

    Assert::notNull(
      $persisted,
      'Provider verification remains retrievable by its unique reference.'
    );

    Assert::equals(
      $verification->id(),
      $persisted->id(),
      'Only the original verification owns the provider reference.'
    );

    $integrations->unregister($provider->slug());
  }
}
