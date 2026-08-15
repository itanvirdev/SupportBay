<?php

declare(strict_types=1);

namespace SupportBay\Modules\Activities\Listeners;

use SupportBay\Common\Enums\AuthorType;
use SupportBay\Core\Events\Contracts\Listener;
use SupportBay\Modules\Activities\Enums\ActivityType;
use SupportBay\Modules\Activities\Services\ActivityService;
use SupportBay\Modules\Tickets\Events\TicketSlaBreached;

final class LogTicketSlaBreachedActivity implements Listener {
  public function __construct(private readonly ActivityService $activities) {}
  public function handle(object $event): void {
    if (! $event instanceof TicketSlaBreached) { return; }
    $this->activities->create([
      'ticket_id'=>$event->ticket()->id(),
      'actor_type'=>AuthorType::SYSTEM->value,
      'event_type'=>ActivityType::TICKET_SLA_BREACHED->value,
      'description'=>'First-response SLA breached.',
      'payload'=>['metric'=>$event->breach()->metric(),'target_minutes'=>$event->breach()->targetMinutes(),'breached_at'=>$event->breach()->breachedAt()],
    ]);
  }
}
