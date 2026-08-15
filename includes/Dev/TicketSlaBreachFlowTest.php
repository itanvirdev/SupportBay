<?php

declare(strict_types=1);

namespace SupportBay\Dev;

use DateTimeImmutable;
use SupportBay\Common\Enums\AuthorType;
use SupportBay\Common\Enums\SourceType;
use SupportBay\Core\Database\DatabaseInstaller;
use SupportBay\Core\Testing\Assert;
use SupportBay\Core\Testing\FlowTest;
use SupportBay\Modules\Activities\Enums\ActivityType;
use SupportBay\Modules\Activities\Repositories\ActivityRepository;
use SupportBay\Modules\Activities\Services\ActivityService;
use SupportBay\Modules\Tickets\Enums\TicketPriority;
use SupportBay\Modules\Tickets\Enums\TicketState;
use SupportBay\Modules\Tickets\Enums\TicketStatus;
use SupportBay\Modules\Tickets\Repositories\TicketRepository;
use SupportBay\Modules\Tickets\Repositories\TicketSlaBreachRepository;
use SupportBay\Modules\Tickets\Services\TicketSlaBreachService;
use SupportBay\Modules\Tickets\Services\TicketSlaBreachWorker;
use SupportBay\Modules\Tickets\Services\TicketSlaPolicyService;
use SupportBay\Modules\Notifications\Repositories\NotificationLogRepository;
use SupportBay\Modules\Notifications\Enums\NotificationStatus;

final class TicketSlaBreachFlowTest extends FlowTest {
  protected static function title(): string { return 'Ticket SLA Breach Flow Test'; }

  protected static function execute(...$services): void {
    /** @var TicketSlaBreachService $detector */
    /** @var TicketSlaBreachRepository $breaches */
    /** @var TicketSlaPolicyService $policies */
    /** @var TicketRepository $tickets */
    /** @var ActivityService $activities */
    /** @var ActivityRepository $activityRepository */
    /** @var NotificationLogRepository $notificationLogs */
    [$detector,$breaches,$policies,$tickets,$activities,$activityRepository,$notificationLogs] = $services;
    DatabaseInstaller::install();
    $existing = get_option('sbay_ticket_sla_policy', null);
    $existingPreferences = get_option('sbay_notification_preferences', null);
    $existingTemplates = get_option('sbay_notification_templates', null);
    $now = new DateTimeImmutable(current_time('mysql'));
    $ticketId = 0;
    try {
      delete_option('sbay_notification_preferences');
      delete_option('sbay_notification_templates');
      $policies->update(['enabled'=>true,'first_response_minutes'=>['normal'=>15]]);
      $ticketId = $tickets->create([
        'track_id'=>strtoupper(substr(wp_generate_password(9,false,false),0,9)),
        'customer_id'=>1,'department_id'=>1,'assigned_agent_id'=>1,'subject'=>'SLA breach detector',
        'created_by_type'=>AuthorType::CUSTOMER->value,'status'=>TicketStatus::OPEN->value,
        'state'=>TicketState::ACTIVE->value,'priority'=>TicketPriority::NORMAL->value,
        'source'=>SourceType::WEB->value,'created_at'=>$now->modify('-30 minutes')->format('Y-m-d H:i:s'),
        'updated_at'=>$now->format('Y-m-d H:i:s'),
      ]);
      $first=$detector->detect(20,$now->format('Y-m-d H:i:s'));
      $second=$detector->detect(20,$now->format('Y-m-d H:i:s'));
      $breach=$breaches->findByTicket($ticketId);
      $timeline=array_filter($activities->getByTicket($ticketId),static fn($activity):bool=>$activity->eventType()===ActivityType::TICKET_SLA_BREACHED);
      $notification=array_values(array_filter($notificationLogs->findByTicket($ticketId),static fn($log):bool=>$log->event()==='ticket_sla_breached'));
      Assert::true($first['detected']===1&&$first['dispatched']===1&&$second['detected']===0&&$second['dispatched']===0,'Repeated detection emits the SLA breach domain event only once.');
      Assert::true($breach!==null&&$breach->targetMinutes()===15&&count($timeline)===1,'Durable breach evidence and one system timeline activity are recorded.');
      Assert::true(count($notification)===1&&$notification[0]->status()===NotificationStatus::PENDING&&str_contains((string)$notification[0]->subject(),'SLA breached'),'One asynchronous SLA breach notification is queued for the assigned staff user.');
      Assert::true(has_action(TicketSlaBreachWorker::HOOK)!==false&&wp_next_scheduled(TicketSlaBreachWorker::HOOK)!==false,'Bounded recurring SLA breach detection is registered and scheduled.');
      $policies->update(['enabled'=>false]);
      Assert::equals(0,$detector->detect(20,$now->format('Y-m-d H:i:s'))['detected'],'Disabled SLA policy suppresses detection.');
    } finally {
      if($ticketId>0){$notificationLogs->deleteByTicket($ticketId);$activityRepository->deleteByTicket($ticketId);$breaches->deleteByTicket($ticketId);$tickets->delete($ticketId);}
      if($existing===null){delete_option('sbay_ticket_sla_policy');}else{update_option('sbay_ticket_sla_policy',$existing,false);}
      if($existingPreferences===null){delete_option('sbay_notification_preferences');}else{update_option('sbay_notification_preferences',$existingPreferences,false);}
      if($existingTemplates===null){delete_option('sbay_notification_templates');}else{update_option('sbay_notification_templates',$existingTemplates,false);}
    }
  }
}
