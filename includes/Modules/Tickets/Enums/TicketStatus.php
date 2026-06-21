<?php

declare(strict_types=1);

namespace SupportBay\Modules\Tickets\Enums;

enum TicketStatus: string {
  case OPEN = 'open';
  case PENDING = 'pending';
  case ANSWERED = 'answered';
  case RESOLVED = 'resolved';
  case CLOSED = 'closed';

  /**
   * Default status for new tickets
   */
  public static function default(): self {
    return self::OPEN;
  }

  /**
   * Check if ticket is final state
   */
  public function isFinal(): bool {
    return in_array($this, [
      self::RESOLVED,
      self::CLOSED,
    ], true);
  }

  /**
   * Allow customer reply?
   */
  public function canReceiveReplies(): bool {
    return in_array($this, [
      self::OPEN,
      self::PENDING,
      self::ANSWERED,
    ], true);
  }

  /**
   * Check if ticket is closed
   */
  public function isClosed(): bool {
    return $this === self::CLOSED;
  }
}
