<?php

declare(strict_types=1);

namespace SupportBay\Modules\Tickets\Events;

use SupportBay\Core\Events\AbstractEvent;
use SupportBay\Modules\Tickets\Entities\Ticket;

final class TicketClosed extends AbstractEvent {
  public function __construct(
    private readonly Ticket $ticket,
  ) {
  }

  /**
   * Closed ticket entity.
   */
  public function ticket(): Ticket {
    return $this->ticket;
  }
}
