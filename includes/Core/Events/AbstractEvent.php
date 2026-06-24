<?php

declare(strict_types=1);

namespace SupportBay\Core\Events;

use SupportBay\Core\Events\Contracts\Event;

abstract class AbstractEvent implements Event {
  /**
   * Event occurrence timestamp.
   */
  private readonly string $occurredAt;

  /**
   * Create a new event.
   */
  public function __construct() {
    $this->occurredAt = current_time('mysql');
  }

  /**
   * Get the event name.
   */
  public function name(): string {
    return static::class;
  }

  /**
   * Event occurrence timestamp.
   */
  public function occurredAt(): string {
    return $this->occurredAt;
  }

  /**
   * Convert event to array.
   */
  public function toArray(): array {
    return [
      'name'        => $this->name(),
      'occurred_at' => $this->occurredAt(),
    ];
  }
}
