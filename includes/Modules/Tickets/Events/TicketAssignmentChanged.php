<?php

declare(strict_types=1);

namespace SupportBay\Modules\Tickets\Events;

use SupportBay\Core\Events\AbstractEvent;
use SupportBay\Modules\Tickets\Entities\Ticket;

final class TicketAssignmentChanged extends AbstractEvent {
  public function __construct(
    private readonly Ticket $ticket,
    private readonly ?int $previousAgentId,
    private readonly int $actorId,
  ) {
    parent::__construct();
  }

  public function ticket(): Ticket { return $this->ticket; }
  public function previousAgentId(): ?int { return $this->previousAgentId; }
  public function actorId(): int { return $this->actorId; }

  public function isAssignment(): bool {
    return $this->previousAgentId === null
      && $this->ticket->assignedAgentId() !== null;
  }

  public function isReassignment(): bool {
    return $this->previousAgentId !== null
      && $this->ticket->assignedAgentId() !== null
      && $this->previousAgentId !== $this->ticket->assignedAgentId();
  }

  public function isUnassignment(): bool {
    return $this->ticket->assignedAgentId() === null;
  }
}
