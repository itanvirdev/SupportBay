<?php

declare(strict_types=1);

namespace SupportBay\Modules\Tickets\Events;

use SupportBay\Core\Events\AbstractEvent;
use SupportBay\Modules\Tickets\Entities\Ticket;

final class TicketSplit extends AbstractEvent {
  public function __construct(
    private readonly Ticket $source,
    private readonly Ticket $created,
    private readonly int $actorId,
  ) {
    parent::__construct();
  }

  public function source(): Ticket {
    return $this->source;
  }

  public function created(): Ticket {
    return $this->created;
  }

  public function actorId(): int {
    return $this->actorId;
  }
}
