<?php

declare(strict_types=1);

namespace SupportBay\Common\Enums;

enum AuthorType: string {
  case CUSTOMER = 'customer';
  case GUEST = 'guest';
  case AGENT = 'agent';
  case MANAGER = 'manager';
  case SYSTEM = 'system';

  public static function default(): self {
    return self::CUSTOMER;
  }

  public function isStaff(): bool {
    return match ($this) {
      self::AGENT,
      self::MANAGER => true,

      default => false,
    };
  }

  public function isHuman(): bool {
    return $this !== self::SYSTEM;
  }
}
