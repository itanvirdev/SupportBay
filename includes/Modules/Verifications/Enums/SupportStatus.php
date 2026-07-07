<?php

declare(strict_types=1);

namespace SupportBay\Modules\Verifications\Enums;

/**
 * Support status.
 *
 * Represents the current support entitlement of a verified purchase.
 */
enum SupportStatus: string {
  /**
   * Support is currently active.
   */
  case ACTIVE = 'active';

  /**
   * Support period has expired.
   */
  case EXPIRED = 'expired';

  /**
   * Support status could not be determined.
   */
  case UNKNOWN = 'unknown';

  /**
   * Determine whether support is active.
   */
  public function isActive(): bool {
    return $this === self::ACTIVE;
  }

  /**
   * Determine whether support has expired.
   */
  public function isExpired(): bool {
    return $this === self::EXPIRED;
  }

  /**
   * Determine whether support status is unknown.
   */
  public function isUnknown(): bool {
    return $this === self::UNKNOWN;
  }

  /**
   * Determine whether ticket creation is allowed.
   *
   * Business rules may later become configurable, but by default
   * SupportBay allows ticket creation for active and unknown support.
   */
  public function allowsTicketCreation(): bool {
    return match ($this) {
      self::ACTIVE,
      self::UNKNOWN => true,

      self::EXPIRED => false,
    };
  }

  /**
   * Determine whether support should be refreshed.
   */
  public function canRefresh(): bool {
    return true;
  }
}
