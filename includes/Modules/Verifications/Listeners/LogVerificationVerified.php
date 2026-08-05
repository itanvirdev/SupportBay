<?php

declare(strict_types=1);

namespace SupportBay\Modules\Verifications\Listeners;

use SupportBay\Modules\Verifications\Events\VerificationVerified;

final class LogVerificationVerified {
  /**
   * Handle event.
   */
  public function handle(
    VerificationVerified $event,
  ): void {

    /**
     * Future implementation.
     *
     * ActivityService will log:
     *
     * - Verification successful
     * - Provider
     * - Product
     * - License
     * - Support expiry
     */
  }
}
