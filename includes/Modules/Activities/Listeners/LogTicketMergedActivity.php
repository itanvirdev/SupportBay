<?php

declare(strict_types=1);

namespace SupportBay\Modules\Activities\Listeners;

use SupportBay\Common\Enums\AuthorType;
use SupportBay\Core\Events\Contracts\Listener;
use SupportBay\Modules\Activities\Enums\ActivityType;
use SupportBay\Modules\Activities\Services\ActivityService;

final class LogTicketMergedActivity implements Listener {
  public function __construct(private readonly ActivityService $activities) {}

  public function handle(object $event): void {
    $this->activities->create([
      'ticket_id' => $event->target()->id(),
      'actor_id' => $event->actorId(),
      'actor_type' => AuthorType::AGENT->value,
      'event_type' => ActivityType::TICKET_MERGED->value,
      'description' => sprintf('Ticket #%s merged into this ticket.', $event->source()->trackId()),
      'payload' => ['source_ticket_id' => $event->source()->id()],
    ]);
    $this->activities->create([
      'ticket_id' => $event->source()->id(),
      'actor_id' => $event->actorId(),
      'actor_type' => AuthorType::AGENT->value,
      'event_type' => ActivityType::TICKET_MERGED->value,
      'description' => sprintf('Merged into ticket #%s.', $event->target()->trackId()),
      'payload' => ['target_ticket_id' => $event->target()->id()],
    ]);
  }
}
