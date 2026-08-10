<?php

declare(strict_types=1);

namespace SupportBay\Modules\Tickets\Events;

use SupportBay\Core\Events\AbstractEvent;
use SupportBay\Modules\Tickets\Entities\Ticket;

final class TicketMerged extends AbstractEvent {
  public function __construct(
    private readonly Ticket $source,
    private readonly Ticket $target,
    private readonly int $actorId,
  ) {
    parent::__construct();
  }

  public function source(): Ticket {
    return $this->source;
  }

  public function target(): Ticket {
    return $this->target;
  }

  public function actorId(): int {
    return $this->actorId;
  }
}
