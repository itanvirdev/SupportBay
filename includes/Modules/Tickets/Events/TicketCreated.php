<?php

declare(strict_types=1);

namespace SupportBay\Modules\Tickets\Events;

use SupportBay\Core\Events\AbstractEvent;
use SupportBay\Modules\Tickets\Entities\Ticket;

final class TicketCreated extends AbstractEvent {
  public function __construct(
    private readonly Ticket $ticket,
  ) {
    parent::__construct();
  }

  public function name(): string {
    return 'ticket.created';
  }

  public function ticket(): Ticket {
    return $this->ticket;
  }
}
