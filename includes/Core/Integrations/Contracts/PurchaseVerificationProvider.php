<?php

declare(strict_types=1);

namespace SupportBay\Core\Integrations\Contracts;

use SupportBay\Core\Integrations\Data\PurchaseVerificationData;

/**
 * Purchase verification capability.
 *
 * Implemented by integrations that can verify purchases,
 * orders, licenses, subscriptions, or similar references.
 */
interface PurchaseVerificationProvider {
  /**
   * Verify a provider-specific purchase reference.
   *
   * Examples:
   *
   * - Envato purchase code
   * - EDD license key
   * - WooCommerce order ID
   * - Freemius license ID
   * - Paddle subscription ID
   *
   * The returned data must be normalized and must not expose
   * provider-specific response structures to SupportBay modules.
   *
   * @param array<string, mixed> $context
   */
  public function verifyPurchase(
    string $reference,
    array $context = [],
  ): PurchaseVerificationData;
}
