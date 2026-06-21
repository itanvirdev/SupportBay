<?php

declare(strict_types=1);

namespace SupportBay\Modules\Tickets\Enums;

enum TicketPriority: string {
  case NORMAL = 'normal';
  case MEDIUM = 'medium';
  case HIGH = 'high';
  case URGENT = 'urgent';

  /**
   * Default priority (customer cannot set this in v1)
   */
  public static function default(): self {
    return self::NORMAL;
  }

  /**
   * Can auto-escalate?
   */
  public function requiresAttention(): bool {
    return in_array($this, [
      self::HIGH,
      self::URGENT,
    ], true);
  }

  /**
   * Numeric weight (useful for sorting queues)
   */
  public function weight(): int {
    return match ($this) {
      self::NORMAL => 1,
      self::MEDIUM => 2,
      self::HIGH => 3,
      self::URGENT => 4,
    };
  }
}
