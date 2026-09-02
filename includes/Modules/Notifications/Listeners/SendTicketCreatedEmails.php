<?php

declare(strict_types=1);

namespace SupportBay\Modules\Notifications\Listeners;

use SupportBay\Core\Events\Contracts\Event;
use SupportBay\Core\Events\Contracts\Listener;
use SupportBay\Modules\Customers\Services\CustomerService;
use SupportBay\Modules\Customers\Repositories\WordPressUserRepository;
use SupportBay\Modules\Notifications\Data\NotificationData;
use SupportBay\Modules\Notifications\Enums\NotificationRecipientType;
use SupportBay\Modules\Notifications\Services\NotificationService;
use SupportBay\Modules\Notifications\Services\NotificationPreferenceService;
use SupportBay\Modules\Notifications\Services\NotificationTemplateService;
use SupportBay\Modules\Tickets\Events\TicketCreated;
use SupportBay\Modules\Settings\Services\WeekendHolidaySettingsService;

final class SendTicketCreatedEmails implements Listener {
  public function __construct(
    private readonly NotificationService $notifications,
    private readonly NotificationTemplateService $templates,
    private readonly NotificationPreferenceService $preferences,
    private readonly CustomerService $customers,
    private readonly WordPressUserRepository $users,
    private readonly WeekendHolidaySettingsService $availability,
  ) {
  }

  public function handle(Event $event): void {
    if (! $event instanceof TicketCreated) {
      return;
    }

    $ticket = $event->ticket();
    $context = [
      'site_name' => get_bloginfo('name'),
      'site_url' => home_url('/'),
      'current_date' => wp_date((string) get_option('date_format')),
      'ticket_id' => $ticket->id(),
      'track_id' => $ticket->trackId(),
      'ticket_subject' => $ticket->subject(),
      'ticket_url' => home_url('/support/tickets/' . $ticket->id() . '/'),
    ];
    $agentTemplate = $this->preferences->allows(
      'ticket_created',
      NotificationRecipientType::AGENT,
    ) ? $this->templates->render(
      'ticket_created',
      NotificationRecipientType::AGENT,
      $context,
    ) : null;

    if ($agentTemplate) {
      $this->notifications->enqueue(new NotificationData(
        event: 'ticket_created',
        recipient: (string) get_option('admin_email'),
        subject: $agentTemplate->subject,
        content: $agentTemplate->htmlContent,
        headers: ['Content-Type: text/html; charset=UTF-8'],
        metadata: ['ticket_id' => $ticket->id()],
      ));
    }

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

    $customerTemplate = $this->preferences->allows(
      'ticket_created',
      NotificationRecipientType::CUSTOMER,
    ) ? $this->templates->render(
      'ticket_created',
      NotificationRecipientType::CUSTOMER,
      [
        ...$context,
        'customer_name' => (string) $user->display_name,
        'customer_email' => (string) $user->user_email,
      ],
    ) : null;

    if ($customerTemplate) {
      $this->notifications->enqueue(new NotificationData(
        event: 'ticket_created',
        recipient: (string) $user->user_email,
        subject: $customerTemplate->subject,
        content: $customerTemplate->htmlContent,
        headers: ['Content-Type: text/html; charset=UTF-8'],
        metadata: [
          'ticket_id' => $ticket->id(),
          'user_id'   => $customer->userId(),
        ],
      ));
    }

    $availability=$this->availability->get();
    $active=$this->availability->activeState($availability);
    foreach(['weekend','holiday'] as $type){
      if(! $active[$type]||! $availability[$type.'_email_enabled'])continue;
      $content=strtr((string)$availability[$type.'_email_content'],[
        '{{ticket_user}}'=>(string)$user->display_name,
        '{{site_name}}'=>(string)get_bloginfo('name'),
      ]);
      $this->notifications->enqueue(new NotificationData(
        event:$type.'_ticket_notice',
        recipient:(string)$user->user_email,
        subject:sprintf('%s support availability notice',(string)get_bloginfo('name')),
        content:$content,
        metadata:['ticket_id'=>$ticket->id(),'user_id'=>$customer->userId(),'availability_type'=>$type],
      ));
    }
  }
}
