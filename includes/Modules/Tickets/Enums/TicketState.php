<?php

declare(strict_types=1);

namespace SupportBay\Modules\Tickets\Enums;

enum TicketState: string {
  case ACTIVE = 'active';
  case INACTIVE = 'inactive';
  case TRASH = 'trash';

  public static function default(): self {
    return self::ACTIVE;
  }

  /**
   * Can ticket be accessed?
   */
  public function isAccessible(): bool {
    return $this !== self::TRASH;
  }
}
