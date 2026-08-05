<?php

declare(strict_types=1);

namespace SupportBay\Modules\Verifications\Listeners;

use SupportBay\Modules\Verifications\Events\VerificationCreated;

final class LogVerificationCreated {
  /**
   * Handle event.
   */
  public function handle(
    VerificationCreated $event,
  ): void {

    /**
     * Future implementation.
     *
     * ActivityService will log:
     *
     * - Verification created
     * - Provider
     * - Provider reference
     * - Customer
     *
     * Currently a verification is not yet linked
     * to a ticket, therefore no activity is created.
     */
  }
}
