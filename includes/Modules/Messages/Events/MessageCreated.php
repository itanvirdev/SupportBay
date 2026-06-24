<?php

declare(strict_types=1);

namespace SupportBay\Modules\Messages\Events;

use SupportBay\Core\Events\AbstractEvent;
use SupportBay\Modules\Messages\Entities\Message;

final class MessageCreated extends AbstractEvent {
  /**
   * Message entity.
   */
  private readonly Message $message;

  /**
   * Create a new event.
   */
  public function __construct(Message $message) {
    parent::__construct();

    $this->message = $message;
  }

  /**
   * Get event name.
   */
  public function name(): string {
    return 'message.created';
  }

  /**
   * Get message.
   */
  public function message(): Message {
    return $this->message;
  }
}
