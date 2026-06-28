<?php

declare(strict_types=1);

namespace SupportBay\Modules\Customers\Enums;

enum CustomerState: string {
  case GUEST = 'guest';

  case REGISTERED = 'registered';

  case VERIFIED = 'verified';

  case SUSPENDED = 'suspended';

  /**
   * Default customer state.
   */
  public static function default(): self {
    return self::GUEST;
  }

  /**
   * Can create tickets?
   */
  public function canCreateTickets(): bool {
    return match ($this) {
      self::GUEST,
      self::REGISTERED,
      self::VERIFIED => true,

      self::SUSPENDED => false,
    };
  }

  /**
   * Can authenticate?
   */
  public function canLogin(): bool {
    return match ($this) {
      self::GUEST,
      self::REGISTERED,
      self::VERIFIED => true,

      self::SUSPENDED => false,
    };
  }

  /**
   * Is verified customer?
   */
  public function isVerified(): bool {
    return $this === self::VERIFIED;
  }

  /**
   * Is guest customer?
   */
  public function isGuest(): bool {
    return $this === self::GUEST;
  }

  /**
   * Is registered customer?
   */
  public function isRegistered(): bool {
    return $this === self::REGISTERED;
  }

  /**
   * Is suspended customer?
   */
  public function isSuspended(): bool {
    return $this === self::SUSPENDED;
  }
}
