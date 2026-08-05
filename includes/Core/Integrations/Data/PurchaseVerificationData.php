<?php

declare(strict_types=1);

namespace SupportBay\Core\Integrations\Data;

final readonly class PurchaseVerificationData {
  /**
   * Constructor.
   */
  public function __construct(
    private string $provider,
    private string $providerReference,
    private ?string $providerCustomerReference,
    private ?string $productId,
    private ?string $productName,
    private ?string $licenseType,
    private ?string $supportExpiresAt,
    private ?string $purchasedAt,
    private string $status,
    private array $snapshot = [],
  ) {
  }

  /**
   * Convert data to array.
   *
   * @return array<string, mixed>
   */
  public function toArray(): array {
    return [
      'provider'                    => $this->provider,
      'provider_reference'          => $this->providerReference,
      'provider_customer_reference' => $this->providerCustomerReference,
      'product_id'                  => $this->productId,
      'product_name'                => $this->productName,
      'license_type'                => $this->licenseType,
      'support_expires_at'          => $this->supportExpiresAt,
      'purchased_at'                => $this->purchasedAt,
      'verification_status'         => $this->status,
      'provider_snapshot'           => $this->snapshot,
    ];
  }

  // -----------------------------------------------------------------
  // Getters
  // -----------------------------------------------------------------

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
   * Provider customer reference.
   */
  public function providerCustomerReference(): ?string {
    return $this->providerCustomerReference;
  }

  /**
   * Product ID.
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
   * Support expiration.
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
   * Verification status.
   */
  public function status(): string {
    return $this->status;
  }

  /**
   * Provider snapshot.
   *
   * @return array<string, mixed>
   */
  public function snapshot(): array {
    return $this->snapshot;
  }

  /*
  |--------------------------------------------------------------------------
  | Domain Helpers
  |--------------------------------------------------------------------------
  */

  /**
   * Is verification valid?
   */
  public function isVerified(): bool {
    return $this->status === 'verified';
  }

  /**
   * Has product?
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
   * Has provider snapshot?
   */
  public function hasSnapshot(): bool {
    return ! empty($this->snapshot);
  }
}
