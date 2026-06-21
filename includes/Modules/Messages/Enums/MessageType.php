<?php

declare(strict_types=1);

namespace SupportBay\Modules\Messages\Enums;

enum MessageType: string {
  /**
   * Customer or agent conversation message.
   * Visible in ticket thread.
   */
  case REPLY = 'reply';

  /**
   * Internal staff-only note.
   * Not visible to customers.
   */
  case INTERNAL_NOTE = 'internal_note';

  /**
   * System-generated message.
   * Example: ticket assigned, status changed, automation events.
   */
  case SYSTEM = 'system';

  /**
   * Default message type when not explicitly provided.
   */
  public static function default(): self {
    return self::REPLY;
  }

  /**
   * Whether this message is visible to the customer.
   */
  public function isVisibleToCustomer(): bool {
    return match ($this) {
      self::REPLY => true,
      self::SYSTEM => true, // system messages may be visible depending on content policy
      self::INTERNAL_NOTE => false,
    };
  }

  /**
   * Whether this message can be edited after creation.
   */
  public function isEditable(): bool {
    return match ($this) {
      self::SYSTEM => false,
      default => true,
    };
  }

  /**
   * Whether this message should trigger customer notifications.
   */
  public function triggersCustomerNotification(): bool {
    return match ($this) {
      self::REPLY => true,
      self::INTERNAL_NOTE => false,
      self::SYSTEM => false, // controlled by event type later
    };
  }

  /**
   * Whether this message affects ticket timeline updates.
   */
  public function affectsTicketTimeline(): bool {
    return match ($this) {
      self::REPLY => true,
      self::INTERNAL_NOTE => true,
      self::SYSTEM => true,
    };
  }
}
