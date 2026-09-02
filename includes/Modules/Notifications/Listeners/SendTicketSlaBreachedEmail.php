<?php

declare(strict_types=1);

namespace SupportBay\Modules\Notifications\Listeners;

use DateTimeImmutable;
use SupportBay\Core\Authorization\CapabilityManager;
use SupportBay\Core\Events\Contracts\Event;
use SupportBay\Core\Events\Contracts\Listener;
use SupportBay\Modules\Customers\Repositories\WordPressUserRepository;
use SupportBay\Modules\Notifications\Data\NotificationData;
use SupportBay\Modules\Notifications\Enums\NotificationRecipientType;
use SupportBay\Modules\Notifications\Services\NotificationPreferenceService;
use SupportBay\Modules\Notifications\Services\NotificationService;
use SupportBay\Modules\Notifications\Services\NotificationTemplateService;
use SupportBay\Modules\Tickets\Events\TicketSlaBreached;

final class SendTicketSlaBreachedEmail implements Listener {
  public function __construct(
    private readonly NotificationService $notifications,
    private readonly NotificationTemplateService $templates,
    private readonly NotificationPreferenceService $preferences,
    private readonly WordPressUserRepository $users,
  ) {
  }

  public function handle(Event $event): void {
    if (! $event instanceof TicketSlaBreached) { return; }
    $ticket = $event->ticket();
    $agentId = $ticket->assignedAgentId();
    if ($agentId === null || ! $this->preferences->allows('ticket_sla_breached', NotificationRecipientType::AGENT)) { return; }
    $agent = $this->users->find($agentId);
    if (! $agent || ! user_can($agent, CapabilityManager::VIEW_TICKETS)) { return; }
    $breach = $event->breach();
    $dueAt = (new DateTimeImmutable($ticket->createdAt()))->modify('+' . $breach->targetMinutes() . ' minutes');
    $breachedAt = new DateTimeImmutable($breach->breachedAt());
    $overdue = max(0, (int) floor(($breachedAt->getTimestamp() - $dueAt->getTimestamp()) / 60));
    $template = $this->templates->render('ticket_sla_breached', NotificationRecipientType::AGENT, [
      'site_name'=>get_bloginfo('name'),'site_url'=>home_url('/'),'current_date'=>wp_date((string)get_option('date_format')),
      'agent_name'=>(string)$agent->display_name,'agent_email'=>(string)$agent->user_email,
      'ticket_id'=>$ticket->id(),'track_id'=>$ticket->trackId(),'ticket_subject'=>$ticket->subject(),
      'ticket_status'=>$ticket->status()->value,'ticket_priority'=>$ticket->priority()->value,
      'ticket_url'=>admin_url('admin.php?page=supportbay&ticket='.$ticket->id()),
      'sla_target_minutes'=>$breach->targetMinutes(),'sla_breached_at'=>$breach->breachedAt(),'sla_overdue_minutes'=>$overdue,
    ]);
    if (! $template) { return; }
    $this->notifications->enqueue(new NotificationData(
      event:'ticket_sla_breached',recipient:(string)$agent->user_email,subject:$template->subject,
      content:$template->htmlContent,
      headers:['Content-Type: text/html; charset=UTF-8'],
      metadata:['ticket_id'=>$ticket->id(),'agent_id'=>$agentId,'sla_breach_id'=>$breach->id(),'sla_metric'=>$breach->metric()],
    ));
  }
}
