<?php

declare(strict_types=1);

namespace SupportBay\Modules\Verifications\Listeners;

use SupportBay\Modules\Verifications\Events\VerificationRevoked;

final class LogVerificationRevoked {
  /**
   * Handle event.
   */
  public function handle(
    VerificationRevoked $event,
  ): void {

    /**
     * Future implementation.
     *
     * ActivityService will log:
     *
     * - Verification revoked
     * - Provider
     * - Provider reference
     */
  }
}
