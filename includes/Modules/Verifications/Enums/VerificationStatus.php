<?php

declare(strict_types=1);

namespace SupportBay\Modules\Verifications\Enums;

/**
 * Verification lifecycle status.
 *
 * Represents the validity of a verification record.
 */
enum VerificationStatus: string {
  /**
   * Waiting for provider verification.
   */
  case PENDING = 'pending';

  /**
   * Successfully verified by the provider.
   */
  case VERIFIED = 'verified';

  /**
   * Verification remains valid, but support has expired.
   */
  case EXPIRED = 'expired';

  /**
   * Provider could not verify the purchase or license.
   */
  case INVALID = 'invalid';

  /**
   * Purchase or license has been revoked by the provider.
   */
  case REVOKED = 'revoked';

  /**
   * Determine whether the verification is valid.
   */
  public function isValid(): bool {
    return match ($this) {
      self::VERIFIED,
      self::EXPIRED => true,

      default => false,
    };
  }

  /**
   * Determine whether the verification is pending.
   */
  public function isPending(): bool {
    return $this === self::PENDING;
  }

  /**
   * Determine whether the verification has expired.
   */
  public function isExpired(): bool {
    return $this === self::EXPIRED;
  }

  /**
   * Determine whether the verification is invalid.
   */
  public function isInvalid(): bool {
    return $this === self::INVALID;
  }

  /**
   * Determine whether the verification has been revoked.
   */
  public function isRevoked(): bool {
    return $this === self::REVOKED;
  }

  /**
   * Determine whether the verification can be refreshed.
   */
  public function canRefresh(): bool {
    return match ($this) {
      self::VERIFIED,
      self::EXPIRED,
      self::INVALID => true,

      self::PENDING,
      self::REVOKED => false,
    };
  }

  /** @return string[] */
  public static function values(): array {
    return array_map(static fn(self $status): string => $status->value, self::cases());
  }
}
