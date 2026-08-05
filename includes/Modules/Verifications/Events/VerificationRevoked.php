<?php

declare(strict_types=1);

namespace SupportBay\Modules\Verifications\Events;

use SupportBay\Core\Events\AbstractEvent;
use SupportBay\Modules\Verifications\Entities\Verification;

final class VerificationRevoked extends AbstractEvent {
  public function __construct(
    private readonly Verification $verification,
  ) {
  }

  /**
   * Verification entity.
   */
  public function verification(): Verification {
    return $this->verification;
  }

  /**
   * Verification ID.
   */
  public function verificationId(): int {
    return $this->verification->id();
  }

  /**
   * Provider slug.
   */
  public function provider(): string {
    return $this->verification->provider();
  }

  /**
   * Provider reference.
   */
  public function providerReference(): string {
    return $this->verification->providerReference();
  }

  /**
   * Customer ID.
   */
  public function customerId(): ?int {
    return $this->verification->customerId();
  }
}
