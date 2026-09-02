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
use SupportBay\Modules\Notifications\Enums\NotificationRecipientType;
use SupportBay\Modules\Notifications\Services\NotificationService;
use SupportBay\Modules\Notifications\Services\NotificationPreferenceService;
use SupportBay\Modules\Notifications\Services\NotificationTemplateService;
use SupportBay\Modules\Tickets\Services\TicketService;

final class SendMessageCreatedEmail implements Listener {
  public function __construct(
    private readonly NotificationService $notifications,
    private readonly NotificationTemplateService $templates,
    private readonly NotificationPreferenceService $preferences,
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
    $recipientType = NotificationRecipientType::AGENT;
    $customerName = '';
    $customerEmail = '';

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
      $recipientType = NotificationRecipientType::CUSTOMER;
      $customerName = (string) $user->display_name;
      $customerEmail = (string) $user->user_email;
    }

    if (! $this->preferences->allows($eventKey, $recipientType)) {
      return;
    }

    $template = $this->templates->render(
      $eventKey,
      $recipientType,
      [
        'site_name' => get_bloginfo('name'),
        'site_url' => home_url('/'),
        'current_date' => wp_date((string) get_option('date_format')),
        'customer_name' => $customerName,
        'customer_email' => $customerEmail,
        'ticket_id' => $ticket->id(),
        'track_id' => $ticket->trackId(),
        'ticket_subject' => $ticket->subject(),
        'ticket_url' => home_url('/support/tickets/' . $ticket->id() . '/'),
        'reply_content' => wp_strip_all_tags($message->content()),
      ],
    );

    if (! $template) {
      return;
    }

    $this->notifications->enqueue(new NotificationData(
      event: $eventKey,
      recipient: $recipient,
      subject: $template->subject,
      content: $template->htmlContent,
      headers: ['Content-Type: text/html; charset=UTF-8'],
      metadata: [
        'ticket_id'  => $ticket->id(),
        'message_id' => $message->id(),
      ],
    ));
  }
}
