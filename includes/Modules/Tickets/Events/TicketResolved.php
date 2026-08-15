<?php

declare(strict_types=1);

namespace SupportBay\Modules\Tickets\Events;

use SupportBay\Core\Events\AbstractEvent;
use SupportBay\Modules\Tickets\Entities\Ticket;

final class TicketResolved extends AbstractEvent {
  public function __construct(
    private readonly Ticket $ticket,
    private readonly int $actorId,
  ) {
    parent::__construct();
  }

  public function ticket(): Ticket { return $this->ticket; }
  public function actorId(): int { return $this->actorId; }
}
