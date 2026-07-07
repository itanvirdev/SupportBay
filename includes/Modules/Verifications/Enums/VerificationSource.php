<?php

declare(strict_types=1);

namespace SupportBay\Modules\Verifications\Enums;

/**
 * Verification source.
 *
 * Indicates how a verification was initiated.
 */
enum VerificationSource: string {
  /**
   * Customer submitted a purchase reference manually.
   */
  case MANUAL = 'manual';

  /**
   * Verification performed during OAuth authentication.
   */
  case OAUTH = 'oauth';

  /**
   * Verification triggered during ticket creation.
   */
  case TICKET_SUBMISSION = 'ticket_submission';

  /**
   * Verification triggered by a scheduled background job.
   */
  case BACKGROUND_SYNC = 'background_sync';

  /**
   * Verification triggered through the public or internal API.
   */
  case API = 'api';

  /**
   * Determine whether the verification was initiated by a customer.
   */
  public function isCustomerInitiated(): bool {
    return match ($this) {
      self::MANUAL,
      self::OAUTH,
      self::TICKET_SUBMISSION => true,

      default => false,
    };
  }

  /**
   * Determine whether the verification was initiated automatically.
   */
  public function isAutomatic(): bool {
    return match ($this) {
      self::BACKGROUND_SYNC,
      self::API => true,

      default => false,
    };
  }

  /**
   * Determine whether the verification can be repeated automatically.
   */
  public function canRefresh(): bool {
    return true;
  }
}
