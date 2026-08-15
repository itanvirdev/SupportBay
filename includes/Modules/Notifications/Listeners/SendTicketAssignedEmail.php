<?php

declare(strict_types=1);

namespace SupportBay\Modules\Notifications\Listeners;

use SupportBay\Core\Authorization\CapabilityManager;
use SupportBay\Core\Events\Contracts\Event;
use SupportBay\Core\Events\Contracts\Listener;
use SupportBay\Modules\Customers\Repositories\WordPressUserRepository;
use SupportBay\Modules\Notifications\Data\NotificationData;
use SupportBay\Modules\Notifications\Enums\NotificationRecipientType;
use SupportBay\Modules\Notifications\Services\NotificationPreferenceService;
use SupportBay\Modules\Notifications\Services\NotificationService;
use SupportBay\Modules\Notifications\Services\NotificationTemplateService;
use SupportBay\Modules\Tickets\Events\TicketAssignmentChanged;

final class SendTicketAssignedEmail implements Listener {
  public function __construct(
    private readonly NotificationService $notifications,
    private readonly NotificationTemplateService $templates,
    private readonly NotificationPreferenceService $preferences,
    private readonly WordPressUserRepository $users,
  ) {
  }

  public function handle(Event $event): void {
    if (! $event instanceof TicketAssignmentChanged || $event->isUnassignment()) {
      return;
    }

    $ticket = $event->ticket();
    $agentId = $ticket->assignedAgentId();
    $eventKey = $event->isReassignment()
      ? 'ticket_reassigned'
      : 'ticket_assigned';

    if ($agentId === null || ! $this->preferences->allows(
      $eventKey,
      NotificationRecipientType::AGENT,
    )) {
      return;
    }

    $agent = $this->users->find($agentId);

    if (! $agent || ! user_can($agent, CapabilityManager::VIEW_TICKETS)) {
      return;
    }

    $template = $this->templates->render(
      $eventKey,
      NotificationRecipientType::AGENT,
      [
        'site_name' => get_bloginfo('name'),
        'site_url' => home_url('/'),
        'current_date' => wp_date((string) get_option('date_format')),
        'agent_name' => (string) $agent->display_name,
        'agent_email' => (string) $agent->user_email,
        'ticket_id' => $ticket->id(),
        'track_id' => $ticket->trackId(),
        'ticket_subject' => $ticket->subject(),
        'ticket_status' => $ticket->status()->value,
        'ticket_priority' => $ticket->priority()->value,
        'ticket_url' => admin_url('admin.php?page=supportbay&ticket=' . $ticket->id()),
      ],
    );

    if (! $template) {
      return;
    }

    $this->notifications->enqueue(new NotificationData(
      event: $eventKey,
      recipient: (string) $agent->user_email,
      subject: $template->subject,
      content: $template->plainTextContent,
      metadata: [
        'ticket_id' => $ticket->id(),
        'agent_id' => $agentId,
        'previous_agent_id' => $event->previousAgentId(),
        'actor_id' => $event->actorId(),
      ],
    ));
  }
}
