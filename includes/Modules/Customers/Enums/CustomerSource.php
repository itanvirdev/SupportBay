<?php

declare(strict_types=1);

namespace SupportBay\Modules\Customers\Enums;

enum CustomerSource: string {
  /**
   * Customer created from a guest ticket.
   */
  case GUEST = 'guest';

  /**
   * Customer registered manually.
   */
  case REGISTRATION = 'registration';

  /**
   * Imported from WordPress.
   */
  case WORDPRESS = 'wordpress';

  /**
   * Authenticated via Provider.
   */
  case PROVIDER = 'provider';

  /**
   * Created by an administrator.
   */
  case ADMIN = 'admin';

  /**
   * Default customer source.
   */
  public static function default(): self {
    return self::GUEST;
  }

  /**
   * Was customer created by a provider?
   */
  public function isProvider(): bool {
    return match ($this) {
      self::ENVATO => true,

      default => false,
    };
  }

  /**
   * Was customer created internally?
   */
  public function isInternal(): bool {
    return ! $this->isProvider();
  }
}
