<?php

declare(strict_types=1);

namespace SupportBay\Modules\Messages\Listeners;

use SupportBay\Core\Events\Contracts\Listener;
use SupportBay\Modules\Messages\Enums\MessageType;
use SupportBay\Modules\Tickets\Services\TicketService;

final class AutoAssignFirstResponderListener implements Listener {
  public function __construct(
    private readonly TicketService $tickets,
  ) {
  }

  public function handle(object $event): void {
    $message = $event->message();

    if (
      ! $message->authorType()->isStaff()
      || $message->type() !== MessageType::REPLY
      || $message->authorId() === null
    ) {
      return;
    }

    $ticket = $this->tickets->find($message->ticketId());

    if (! $ticket || $ticket->isAssigned()) {
      return;
    }

    $this->tickets->changeAssignment(
      $ticket->id(),
      $message->authorId(),
      $message->authorId(),
    );
  }
}
