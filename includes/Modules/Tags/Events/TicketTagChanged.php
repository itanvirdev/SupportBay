<?php

declare(strict_types=1);

namespace SupportBay\Modules\Tags\Events;

use SupportBay\Core\Events\AbstractEvent;
use SupportBay\Modules\Tags\Entities\Tag;
use SupportBay\Modules\Tickets\Entities\Ticket;

final class TicketTagChanged extends AbstractEvent {
  public function __construct(
    private readonly Ticket $ticket,
    private readonly Tag $tag,
    private readonly string $action,
    private readonly int $actorId,
  ) {
    parent::__construct();
  }

  public function ticket(): Ticket { return $this->ticket; }
  public function tag(): Tag { return $this->tag; }
  public function action(): string { return $this->action; }
  public function actorId(): int { return $this->actorId; }
}
