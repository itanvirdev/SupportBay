<?php

declare(strict_types=1);

namespace SupportBay\Providers\Envato\Data;

final readonly class EnvatoPurchase {
  /**
   * Constructor.
   */
  public function __construct(
    private string $purchaseCode,
    private int $productId,
    private string $productName,
    private string $buyer,
    private string $license,
    private string $purchasedAt,
    private ?string $supportedUntil = null,
  ) {
  }

  /**
   * Purchase code.
   */
  public function purchaseCode(): string {
    return $this->purchaseCode;
  }

  /**
   * Envato product ID.
   */
  public function productId(): int {
    return $this->productId;
  }

  /**
   * Product name.
   */
  public function productName(): string {
    return $this->productName;
  }

  /**
   * Buyer username.
   */
  public function buyer(): string {
    return $this->buyer;
  }

  /**
   * License type.
   */
  public function license(): string {
    return $this->license;
  }

  /**
   * Purchase date.
   */
  public function purchasedAt(): string {
    return $this->purchasedAt;
  }

  /**
   * Support expiration.
   */
  public function supportedUntil(): ?string {
    return $this->supportedUntil;
  }

  /**
   * Determine whether support is currently active.
   */
  public function hasActiveSupport(): bool {
    if ($this->supportedUntil === null) {
      return false;
    }

    return strtotime($this->supportedUntil) >= time();
  }

  /**
   * Convert to array.
   *
   * @return array<string, mixed>
   */
  public function toArray(): array {
    return [
      'purchase_code'  => $this->purchaseCode,
      'product_id'     => $this->productId,
      'product_name'   => $this->productName,
      'buyer'          => $this->buyer,
      'license'        => $this->license,
      'purchased_at'   => $this->purchasedAt,
      'supported_until' => $this->supportedUntil,
    ];
  }
}
