<?php

declare(strict_types=1);

namespace SupportBay\Modules\Verifications\Entities;

use SupportBay\Core\Entities\Entity;
use SupportBay\Modules\Verifications\Enums\VerificationStatus;

final class Verification extends Entity {
  public function __construct(
    private int $id,
    private string $provider,
    private string $providerReference,
    private ?int $customerId,
    private ?string $providerCustomerReference,
    private ?string $productId,
    private ?string $productName,
    private ?string $licenseType,
    private ?string $supportExpiresAt,
    private ?string $purchasedAt,
    private ?string $verifiedAt,
    private ?string $lastCheckedAt,
    private VerificationStatus $status,
    private ?string $providerSnapshot,
    private ?string $metadata,
    private string $createdAt,
    private string $updatedAt,
  ) {
  }

  /**
   * Convert entity to array.
   */
  public function toArray(): array {
    return [
      'id'                          => $this->id,
      'provider'                    => $this->provider,
      'provider_reference'          => $this->providerReference,
      'customer_id'                 => $this->customerId,
      'provider_customer_reference' => $this->providerCustomerReference,
      'product_id'                  => $this->productId,
      'product_name'                => $this->productName,
      'license_type'                => $this->licenseType,
      'support_expires_at'          => $this->supportExpiresAt,
      'purchased_at'                => $this->purchasedAt,
      'verified_at'                 => $this->verifiedAt,
      'last_checked_at'             => $this->lastCheckedAt,
      'verification_status'         => $this->status->value,
      'provider_snapshot'           => $this->providerSnapshot,
      'metadata'                    => $this->metadata,
      'created_at'                  => $this->createdAt,
      'updated_at'                  => $this->updatedAt,
    ];
  }

  // -----------------------------------------------------------------
  // Getters
  // -----------------------------------------------------------------

  /**
   * Verification ID.
   */
  public function id(): int {
    return $this->id;
  }

  /**
   * Provider slug.
   */
  public function provider(): string {
    return $this->provider;
  }

  /**
   * Provider reference.
   */
  public function providerReference(): string {
    return $this->providerReference;
  }

  /**
   * Linked customer.
   */
  public function customerId(): ?int {
    return $this->customerId;
  }

  /**
   * Provider customer reference.
   */
  public function providerCustomerReference(): ?string {
    return $this->providerCustomerReference;
  }

  /**
   * Product identifier.
   */
  public function productId(): ?string {
    return $this->productId;
  }

  /**
   * Product name.
   */
  public function productName(): ?string {
    return $this->productName;
  }

  /**
   * License type.
   */
  public function licenseType(): ?string {
    return $this->licenseType;
  }

  /**
   * Support expiration timestamp.
   */
  public function supportExpiresAt(): ?string {
    return $this->supportExpiresAt;
  }

  /**
   * Purchase timestamp.
   */
  public function purchasedAt(): ?string {
    return $this->purchasedAt;
  }

  /**
   * Verification timestamp.
   */
  public function verifiedAt(): ?string {
    return $this->verifiedAt;
  }

  /**
   * Last verification check.
   */
  public function lastCheckedAt(): ?string {
    return $this->lastCheckedAt;
  }

  /**
   * Verification status.
   */
  public function status(): VerificationStatus {
    return $this->status;
  }

  /**
   * Provider snapshot.
   */
  public function providerSnapshot(): ?string {
    return $this->providerSnapshot;
  }

  /**
   * Metadata.
   */
  public function metadata(): ?string {
    return $this->metadata;
  }

  /**
   * Creation timestamp.
   */
  public function createdAt(): string {
    return $this->createdAt;
  }

  /**
   * Last update timestamp.
   */
  public function updatedAt(): string {
    return $this->updatedAt;
  }

  /*
  |--------------------------------------------------------------------------
  | Domain Methods
  |--------------------------------------------------------------------------
  */

  /**
   * Is verification pending?
   */
  public function isPending(): bool {
    return $this->status === VerificationStatus::PENDING;
  }

  /**
   * Is verification successful?
   */
  public function isVerified(): bool {
    return $this->status === VerificationStatus::VERIFIED;
  }

  /**
   * Has support expired?
   */
  public function isExpired(): bool {
    return $this->status === VerificationStatus::EXPIRED;
  }

  /**
   * Is verification invalid?
   */
  public function isInvalid(): bool {
    return $this->status === VerificationStatus::INVALID;
  }

  /**
   * Has verification been revoked?
   */
  public function isRevoked(): bool {
    return $this->status === VerificationStatus::REVOKED;
  }

  /**
   * Is verification valid?
   */
  public function isValid(): bool {
    return $this->status->isValid();
  }

  /**
   * Can this verification be refreshed?
   */
  public function canRefresh(): bool {
    return $this->status->canRefresh();
  }

  /**
   * Linked to a customer?
   */
  public function hasCustomer(): bool {
    return $this->customerId !== null;
  }

  /**
   * Has a product?
   */
  public function hasProduct(): bool {
    return ! empty($this->productId);
  }

  /**
   * Has support expiration?
   */
  public function hasSupportExpiry(): bool {
    return ! empty($this->supportExpiresAt);
  }

  /**
   * Support currently expired?
   */
  public function supportExpired(): bool {
    if (! $this->hasSupportExpiry()) {
      return false;
    }

    return strtotime($this->supportExpiresAt) <= time();
  }

  /**
   * Has provider snapshot?
   */
  public function hasSnapshot(): bool {
    return ! empty($this->providerSnapshot);
  }

  /**
   * Whether this purchase currently grants new-ticket support.
   */
  public function hasActiveSupport(): bool {
    return $this->isVerified()
      && $this->hasSupportExpiry()
      && ! $this->supportExpired();
  }
}
