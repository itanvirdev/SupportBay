<?php

declare(strict_types=1);

namespace SupportBay\Modules\Notifications\Listeners;

use SupportBay\Core\Events\Contracts\Event;
use SupportBay\Core\Events\Contracts\Listener;
use SupportBay\Modules\Customers\Repositories\WordPressUserRepository;
use SupportBay\Modules\Customers\Services\CustomerService;
use SupportBay\Modules\Notifications\Data\NotificationData;
use SupportBay\Modules\Notifications\Enums\NotificationRecipientType;
use SupportBay\Modules\Notifications\Services\NotificationPreferenceService;
use SupportBay\Modules\Notifications\Services\NotificationService;
use SupportBay\Modules\Notifications\Services\NotificationTemplateService;
use SupportBay\Modules\Tickets\Entities\Ticket;
use SupportBay\Modules\Tickets\Events\TicketClosed;
use SupportBay\Modules\Tickets\Events\TicketReopened;
use SupportBay\Modules\Tickets\Events\TicketResolved;

final class SendTicketLifecycleEmail implements Listener {
  public function __construct(
    private readonly NotificationService $notifications,
    private readonly NotificationTemplateService $templates,
    private readonly NotificationPreferenceService $preferences,
    private readonly CustomerService $customers,
    private readonly WordPressUserRepository $users,
  ) {
  }

  public function handle(Event $event): void {
    $eventKey = match (true) {
      $event instanceof TicketClosed => 'ticket_closed',
      $event instanceof TicketResolved => 'ticket_resolved',
      $event instanceof TicketReopened => 'ticket_reopened',
      default => null,
    };

    if ($eventKey === null) {
      return;
    }

    /** @var Ticket $ticket */
    $ticket = $event->ticket();

    if (! $ticket->hasCustomer() || ! $this->preferences->allows(
      $eventKey,
      NotificationRecipientType::CUSTOMER,
    )) {
      return;
    }

    $customer = $this->customers->find($ticket->customerId());
    $user = $customer ? $this->users->find($customer->userId()) : null;

    if (! $customer || ! $user) {
      return;
    }

    $template = $this->templates->render(
      $eventKey,
      NotificationRecipientType::CUSTOMER,
      [
        'site_name' => get_bloginfo('name'),
        'site_url' => home_url('/'),
        'current_date' => wp_date((string) get_option('date_format')),
        'customer_name' => (string) $user->display_name,
        'customer_email' => (string) $user->user_email,
        'ticket_id' => $ticket->id(),
        'track_id' => $ticket->trackId(),
        'ticket_subject' => $ticket->subject(),
        'ticket_status' => $ticket->status()->value,
        'ticket_priority' => $ticket->priority()->value,
        'ticket_url' => home_url('/support/tickets/' . $ticket->id() . '/'),
      ],
    );

    if (! $template) {
      return;
    }

    $this->notifications->enqueue(new NotificationData(
      event: $eventKey,
      recipient: (string) $user->user_email,
      subject: $template->subject,
      content: $template->htmlContent,
      headers: ['Content-Type: text/html; charset=UTF-8'],
      metadata: [
        'ticket_id' => $ticket->id(),
        'user_id' => $customer->userId(),
      ],
    ));
  }
}
