<?php

declare(strict_types=1);

namespace SupportBay\Dev;

use SupportBay\Core\Testing\Assert;
use SupportBay\Core\Testing\FlowTest;
use SupportBay\Modules\Verifications\Enums\VerificationStatus;
use SupportBay\Modules\Verifications\Services\VerificationService;

final class VerificationFlowTest extends FlowTest {
  /**
   * Test title.
   */
  protected static function title(): string {
    return 'Verification Flow Test';
  }

  /**
   * Execute flow.
   */
  protected static function execute(...$services): void {
    /** @var VerificationService $verificationService */
    [$verificationService] = $services;

    echo "🚀 Starting SupportBay Verification Flow Test...\n\n";

    $providerReference = 'TEST-' . strtoupper(
      wp_generate_password(16, false, false)
    );

    // -------------------------------------------------
    // Create Pending Verification
    // -------------------------------------------------

    $verificationId = $verificationService->create([
      'provider'                    => 'envato',
      'provider_reference'          => $providerReference,
      'customer_id'                 => 1,
      'provider_customer_reference' => 'themejunction',
      'verification_status'         => VerificationStatus::PENDING,
      'metadata'                    => [
        'test' => true,
      ],
    ]);

    Assert::true(
      $verificationId > 0,
      'Verification created.'
    );

    // -------------------------------------------------
    // Retrieve Verification
    // -------------------------------------------------

    $verification = $verificationService->find(
      $verificationId
    );

    Assert::notNull(
      $verification,
      'Verification retrieved.'
    );

    Assert::equals(
      $verificationId,
      $verification->id(),
      'Verification ID matches.'
    );

    Assert::equals(
      'envato',
      $verification->provider(),
      'Provider stored.'
    );

    Assert::equals(
      $providerReference,
      $verification->providerReference(),
      'Provider reference stored.'
    );

    Assert::equals(
      1,
      $verification->customerId(),
      'Customer linked.'
    );

    Assert::equals(
      'themejunction',
      $verification->providerCustomerReference(),
      'Provider customer reference stored.'
    );

    Assert::equals(
      VerificationStatus::PENDING,
      $verification->status(),
      'Initial status is pending.'
    );

    Assert::true(
      $verification->isPending(),
      'Pending state detected.'
    );

    Assert::true(
      $verification->hasCustomer(),
      'Customer relationship detected.'
    );

    // -------------------------------------------------
    // Find By Provider Reference
    // -------------------------------------------------

    $foundByReference = $verificationService->findByReference(
      'envato',
      $providerReference
    );

    Assert::notNull(
      $foundByReference,
      'Verification found by provider reference.'
    );

    Assert::equals(
      $verificationId,
      $foundByReference->id(),
      'Provider reference resolves correct verification.'
    );

    // -------------------------------------------------
    // Verify Purchase
    // -------------------------------------------------

    $verified = $verificationService->verify(
      $verificationId,
      [
        'provider_customer_reference' => 'themejunction',
        'product_id'                  => '12345678',
        'product_name'                => 'Rovix WordPress Theme',
        'license_type'                => 'Regular License',
        'support_expires_at'          => gmdate(
          'Y-m-d H:i:s',
          strtotime('+6 months')
        ),
        'purchased_at'                => gmdate(
          'Y-m-d H:i:s',
          strtotime('-1 month')
        ),
        'provider_snapshot'           => [
          'buyer'           => 'themejunction',
          'item_id'         => 12345678,
          'item_name'       => 'Rovix WordPress Theme',
          'license'         => 'Regular License',
          'supported_until' => gmdate(
            'Y-m-d H:i:s',
            strtotime('+6 months')
          ),
          'purchase_date'   => gmdate(
            'Y-m-d H:i:s',
            strtotime('-1 month')
          ),
        ],
      ]
    );

    Assert::equals(
      VerificationStatus::VERIFIED,
      $verified->status(),
      'Verification marked as verified.'
    );

    Assert::true(
      $verified->isVerified(),
      'Verified state detected.'
    );

    Assert::true(
      $verified->isValid(),
      'Verification is valid.'
    );

    Assert::equals(
      '12345678',
      $verified->productId(),
      'Product ID stored.'
    );

    Assert::equals(
      'Rovix WordPress Theme',
      $verified->productName(),
      'Product name stored.'
    );

    Assert::equals(
      'Regular License',
      $verified->licenseType(),
      'License type stored.'
    );

    Assert::true(
      $verified->hasProduct(),
      'Product relationship detected.'
    );

    Assert::true(
      $verified->hasSupportExpiry(),
      'Support expiration stored.'
    );

    Assert::true(
      ! $verified->supportExpired(),
      'Support is currently active.'
    );

    Assert::notNull(
      $verified->verifiedAt(),
      'Verification timestamp stored.'
    );

    Assert::notNull(
      $verified->lastCheckedAt(),
      'Last checked timestamp stored.'
    );

    Assert::true(
      $verified->hasSnapshot(),
      'Provider snapshot stored.'
    );

    $snapshot = json_decode(
      (string) $verified->providerSnapshot(),
      true
    );

    Assert::true(
      is_array($snapshot),
      'Provider snapshot is valid JSON.'
    );

    Assert::equals(
      'themejunction',
      $snapshot['buyer'] ?? null,
      'Snapshot buyer stored.'
    );

    Assert::equals(
      'Rovix WordPress Theme',
      $snapshot['item_name'] ?? null,
      'Snapshot product stored.'
    );

    // -------------------------------------------------
    // Refresh Verification
    // -------------------------------------------------

    $refreshed = $verificationService->refresh(
      $verificationId,
      [
        'verification_status' => VerificationStatus::VERIFIED,
        'support_expires_at'   => gmdate(
          'Y-m-d H:i:s',
          strtotime('+12 months')
        ),
        'provider_snapshot'    => [
          'buyer'           => 'themejunction',
          'item_id'         => 12345678,
          'item_name'       => 'Rovix WordPress Theme',
          'license'         => 'Regular License',
          'supported_until' => gmdate(
            'Y-m-d H:i:s',
            strtotime('+12 months')
          ),
          'refreshed'       => true,
        ],
      ]
    );

    Assert::equals(
      VerificationStatus::VERIFIED,
      $refreshed->status(),
      'Verification remains verified after refresh.'
    );

    Assert::notNull(
      $refreshed->lastCheckedAt(),
      'Refresh timestamp updated.'
    );

    Assert::true(
      $refreshed->canRefresh(),
      'Verification remains refreshable.'
    );

    $refreshedSnapshot = json_decode(
      (string) $refreshed->providerSnapshot(),
      true
    );

    Assert::true(
      ($refreshedSnapshot['refreshed'] ?? false) === true,
      'Refreshed snapshot stored.'
    );

    // -------------------------------------------------
    // Customer Query
    // -------------------------------------------------

    $customerVerifications = $verificationService->findByCustomer(1);

    $customerVerificationFound = false;

    foreach ($customerVerifications as $customerVerification) {
      if ($customerVerification->id() === $verificationId) {
        $customerVerificationFound = true;
        break;
      }
    }

    Assert::true(
      $customerVerificationFound,
      'Verification found in customer records.'
    );

    // -------------------------------------------------
    // Provider Query
    // -------------------------------------------------

    $providerVerifications = $verificationService->findByProvider(
      'envato'
    );

    $providerVerificationFound = false;

    foreach ($providerVerifications as $providerVerification) {
      if ($providerVerification->id() === $verificationId) {
        $providerVerificationFound = true;
        break;
      }
    }

    Assert::true(
      $providerVerificationFound,
      'Verification found in provider records.'
    );

    // -------------------------------------------------
    // Revoke Verification
    // -------------------------------------------------

    $revoked = $verificationService->revoke(
      $verificationId
    );

    Assert::equals(
      VerificationStatus::REVOKED,
      $revoked->status(),
      'Verification revoked.'
    );

    Assert::true(
      $revoked->isRevoked(),
      'Revoked state detected.'
    );

    Assert::true(
      ! $revoked->isValid(),
      'Revoked verification is not valid.'
    );

    Assert::true(
      ! $revoked->canRefresh(),
      'Revoked verification cannot be refreshed.'
    );

    // -------------------------------------------------
    // Clean Up Test Record
    // -------------------------------------------------

    $deleted = $verificationService->delete(
      $verificationId
    );

    Assert::true(
      $deleted,
      'Test verification deleted.'
    );

    Assert::true(
      $verificationService->find($verificationId) === null,
      'Deleted verification no longer exists.'
    );
  }
}
