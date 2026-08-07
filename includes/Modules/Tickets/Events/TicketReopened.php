<?php

declare(strict_types=1);

namespace SupportBay\Modules\Tickets\Events;

use SupportBay\Core\Events\AbstractEvent;
use SupportBay\Modules\Tickets\Entities\Ticket;

final class TicketReopened extends AbstractEvent {
  public function __construct(
    private readonly Ticket $ticket,
  ) {
    parent::__construct();
  }

  /**
   * Reopened ticket entity.
   */
  public function ticket(): Ticket {
    return $this->ticket;
  }
}
