<?php

declare(strict_types=1);

namespace SupportBay\Modules\Tickets\Events;

use SupportBay\Core\Events\AbstractEvent;
use SupportBay\Modules\Tickets\Entities\Ticket;
use SupportBay\Modules\Tickets\Entities\TicketSlaBreach;

final class TicketSlaBreached extends AbstractEvent {
  public function __construct(private readonly Ticket $ticket, private readonly TicketSlaBreach $breach) { parent::__construct(); }
  public function ticket(): Ticket { return $this->ticket; }
  public function breach(): TicketSlaBreach { return $this->breach; }
}
