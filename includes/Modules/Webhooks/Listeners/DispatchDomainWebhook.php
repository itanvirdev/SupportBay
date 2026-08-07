<?php

declare(strict_types=1);

namespace SupportBay\Modules\Webhooks\Listeners;

use SupportBay\Core\Events\Contracts\Event;
use SupportBay\Core\Events\Contracts\Listener;
use SupportBay\Modules\Messages\Events\MessageCreated;
use SupportBay\Modules\Tickets\Events\TicketClosed;
use SupportBay\Modules\Tickets\Events\TicketCreated;
use SupportBay\Modules\Tickets\Events\TicketReopened;
use SupportBay\Modules\Webhooks\Contracts\WebhookDispatcher;
use SupportBay\Modules\Webhooks\Data\WebhookData;

final class DispatchDomainWebhook implements Listener {
  public function __construct(
    private readonly WebhookDispatcher $dispatcher,
  ) {
  }

  public function handle(Event $event): void {
    $data = match (true) {
      $event instanceof TicketCreated => new WebhookData(
        event: 'ticket.created',
        payload: ['ticket' => $event->ticket()->toArray()],
        occurredAt: $event->occurredAt(),
      ),
      $event instanceof TicketClosed => new WebhookData(
        event: 'ticket.closed',
        payload: ['ticket' => $event->ticket()->toArray()],
        occurredAt: $event->occurredAt(),
      ),
      $event instanceof TicketReopened => new WebhookData(
        event: 'ticket.reopened',
        payload: ['ticket' => $event->ticket()->toArray()],
        occurredAt: $event->occurredAt(),
      ),
      $event instanceof MessageCreated => new WebhookData(
        event: 'message.created',
        payload: ['message' => $event->message()->toArray()],
        occurredAt: $event->occurredAt(),
      ),
      default => null,
    };

    if ($data) {
      $this->dispatcher->dispatch($data);
    }
  }
}
