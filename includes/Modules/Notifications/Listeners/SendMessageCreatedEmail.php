<?php

declare(strict_types=1);

namespace SupportBay\Modules\Notifications\Listeners;

use SupportBay\Core\Events\Contracts\Event;
use SupportBay\Core\Events\Contracts\Listener;
use SupportBay\Modules\Customers\Services\CustomerService;
use SupportBay\Modules\Customers\Repositories\WordPressUserRepository;
use SupportBay\Modules\Messages\Events\MessageCreated;
use SupportBay\Modules\Messages\Repositories\MessageRepository;
use SupportBay\Modules\Notifications\Data\NotificationData;
use SupportBay\Modules\Notifications\Services\NotificationService;
use SupportBay\Modules\Tickets\Services\TicketService;

final class SendMessageCreatedEmail implements Listener {
  public function __construct(
    private readonly NotificationService $notifications,
    private readonly TicketService $tickets,
    private readonly CustomerService $customers,
    private readonly MessageRepository $messages,
    private readonly WordPressUserRepository $users,
  ) {
  }

  public function handle(Event $event): void {
    if (! $event instanceof MessageCreated) {
      return;
    }

    $message = $event->message();

    if (! $message->type()->triggersCustomerNotification()) {
      return;
    }

    $ticket = $this->tickets->find($message->ticketId());

    if (! $ticket) {
      return;
    }

    if (
      $message->isFromCustomer()
      && count($this->messages->getByTicket($ticket->id())) === 1
    ) {
      return;
    }

    $recipient = (string) get_option('admin_email');
    $eventKey = 'customer_reply';

    if (! $message->isFromCustomer()) {
      $customer = $ticket->hasCustomer()
        ? $this->customers->find($ticket->customerId())
        : null;

      if (! $customer) {
        return;
      }

      $user = $this->users->find($customer->userId());

      if (! $user) {
        return;
      }

      $recipient = (string) $user->user_email;
      $eventKey = 'ticket_reply';
    }

    $this->notifications->send(new NotificationData(
      event: $eventKey,
      recipient: $recipient,
      subject: sprintf(
        __('New reply on ticket #%s: %s', 'supportbay'),
        $ticket->trackId(),
        $ticket->subject(),
      ),
      content: sprintf(
        __("A new reply was added to ticket #%s.\n\n%s\n\nView: %s", 'supportbay'),
        $ticket->trackId(),
        wp_strip_all_tags($message->content()),
        home_url('/support/tickets/' . $ticket->id() . '/'),
      ),
      metadata: [
        'ticket_id'  => $ticket->id(),
        'message_id' => $message->id(),
      ],
    ));
  }
}
