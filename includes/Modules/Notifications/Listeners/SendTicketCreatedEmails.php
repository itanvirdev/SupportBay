<?php

declare(strict_types=1);

namespace SupportBay\Modules\Notifications\Listeners;

use SupportBay\Core\Events\Contracts\Event;
use SupportBay\Core\Events\Contracts\Listener;
use SupportBay\Modules\Customers\Services\CustomerService;
use SupportBay\Modules\Customers\Repositories\WordPressUserRepository;
use SupportBay\Modules\Notifications\Data\NotificationData;
use SupportBay\Modules\Notifications\Services\NotificationService;
use SupportBay\Modules\Tickets\Events\TicketCreated;

final class SendTicketCreatedEmails implements Listener {
  public function __construct(
    private readonly NotificationService $notifications,
    private readonly CustomerService $customers,
    private readonly WordPressUserRepository $users,
  ) {
  }

  public function handle(Event $event): void {
    if (! $event instanceof TicketCreated) {
      return;
    }

    $ticket = $event->ticket();
    $subject = sprintf(
      __('New support ticket #%s: %s', 'supportbay'),
      $ticket->trackId(),
      $ticket->subject(),
    );
    $content = sprintf(
      __("A new support ticket has been created.\n\nTicket: #%s\nSubject: %s\nView: %s", 'supportbay'),
      $ticket->trackId(),
      $ticket->subject(),
      home_url('/support/tickets/' . $ticket->id() . '/'),
    );

    $this->notifications->send(new NotificationData(
      event: 'ticket_created',
      recipient: (string) get_option('admin_email'),
      subject: $subject,
      content: $content,
      metadata: ['ticket_id' => $ticket->id()],
    ));

    if (! $ticket->hasCustomer()) {
      return;
    }

    $customer = $this->customers->find($ticket->customerId());

    if (! $customer) {
      return;
    }

    $user = $this->users->find($customer->userId());

    if (! $user) {
      return;
    }

    $this->notifications->send(new NotificationData(
      event: 'ticket_created',
      recipient: (string) $user->user_email,
      subject: sprintf(
        __('We received your ticket #%s', 'supportbay'),
        $ticket->trackId(),
      ),
      content: $content,
      metadata: [
        'ticket_id' => $ticket->id(),
        'user_id'   => $customer->userId(),
      ],
    ));
  }
}
