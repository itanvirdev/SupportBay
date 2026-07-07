<?php

declare(strict_types=1);

namespace SupportBay\Modules\Verifications\Repositories;

use SupportBay\Core\Database\Repository;
use SupportBay\Modules\Verifications\Entities\Verification;
use SupportBay\Modules\Verifications\Enums\VerificationStatus;

final class VerificationRepository extends Repository {
  /**
   * Database table.
   */
  protected function table(): string {
    return $this->db->prefix . 'sbay_purchase_verifications';
  }

  /**
   * Hydrate entity.
   */
  protected function hydrate(array $row): Verification {
    return new Verification(
      id: (int) $row['id'],
      provider: $row['provider'],
      providerReference: $row['provider_reference'],
      customerId: $row['customer_id'] !== null
        ? (int) $row['customer_id']
        : null,
      providerCustomerReference: $row['provider_customer_reference'],
      productId: $row['product_id'],
      productName: $row['product_name'],
      licenseType: $row['license_type'],
      supportExpiresAt: $row['support_expires_at'],
      purchasedAt: $row['purchased_at'],
      verifiedAt: $row['verified_at'],
      lastCheckedAt: $row['last_checked_at'],
      status: VerificationStatus::from($row['verification_status']),
      providerSnapshot: $row['provider_snapshot'],
      metadata: $row['metadata'],
      createdAt: $row['created_at'],
      updatedAt: $row['updated_at'],
    );
  }

  /**
   * Find by provider reference.
   */
  public function findByReference(
    string $provider,
    string $reference,
  ): ?Verification {
    /** @var Verification|null */
    return $this->first([
      'provider'           => $provider,
      'provider_reference' => $reference,
    ]);
  }

  /**
   * Find customer verifications.
   *
   * @return Verification[]
   */
  public function findByCustomer(int $customerId): array {
    /** @var Verification[] */
    return $this->findWhere([
      'customer_id' => $customerId,
    ]);
  }

  /**
   * Find provider verifications.
   *
   * @return Verification[]
   */
  public function findByProvider(string $provider): array {
    /** @var Verification[] */
    return $this->findWhere([
      'provider' => $provider,
    ]);
  }

  /**
   * Find by verification status.
   *
   * @return Verification[]
   */
  public function findByStatus(
    VerificationStatus $status,
  ): array {
    /** @var Verification[] */
    return $this->findWhere([
      'verification_status' => $status->value,
    ]);
  }

  /**
   * Find verified purchases for a customer.
   *
   * @return Verification[]
   */
  public function findVerifiedByCustomer(
    int $customerId,
  ): array {
    /** @var Verification[] */
    return $this->findWhere([
      'customer_id'         => $customerId,
      'verification_status' => VerificationStatus::VERIFIED->value,
    ]);
  }
}
