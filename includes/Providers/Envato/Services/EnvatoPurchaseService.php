<?php

declare(strict_types=1);

namespace SupportBay\Providers\Envato\Services;

use SupportBay\Providers\Envato\Api\EnvatoApiClient;

final class EnvatoPurchaseService {
  /**
   * Purchase verification endpoint.
   */
  private const VERIFY_PURCHASE_ENDPOINT = '/v3/market/author/sale';

  /**
   * Constructor.
   */
  public function __construct(
    private readonly EnvatoApiClient $client,
  ) {
  }

  /**
   * Verify an Envato purchase.
   *
   * Returns the purchase information directly from the
   * Envato API.
   *
   * @return array<string, mixed>
   */
  public function verify(
    string $accessToken,
    string $purchaseCode,
  ): array {

    return $this->client->get(
      self::VERIFY_PURCHASE_ENDPOINT,
      $accessToken,
      [
        'code' => $purchaseCode,
      ]
    );
  }

  /**
   * Determine whether support is active.
   */
  public function supportActive(array $purchase): bool {

    if (empty($purchase['supported_until'])) {
      return false;
    }

    return strtotime(
      (string) $purchase['supported_until']
    ) >= time();
  }

  /**
   * Purchase support expiration.
   */
  public function supportExpiry(array $purchase): ?string {
    return $purchase['supported_until'] ?? null;
  }

  /**
   * Product identifier.
   */
  public function productId(array $purchase): ?int {
    return isset($purchase['item']['id'])
      ? (int) $purchase['item']['id']
      : null;
  }

  /**
   * Product name.
   */
  public function productName(array $purchase): ?string {
    return $purchase['item']['name']
      ?? null;
  }

  /**
   * Buyer username.
   */
  public function buyer(array $purchase): ?string {
    return $purchase['buyer']
      ?? null;
  }

  /**
   * License type.
   */
  public function license(array $purchase): ?string {
    return $purchase['license']
      ?? null;
  }

  /**
   * Purchase date.
   */
  public function purchasedAt(array $purchase): ?string {
    return $purchase['sold_at']
      ?? null;
  }
}
