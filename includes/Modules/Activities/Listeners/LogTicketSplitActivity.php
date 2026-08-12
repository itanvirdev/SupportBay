<?php

declare(strict_types=1);

namespace SupportBay\Modules\Activities\Listeners;

use SupportBay\Common\Enums\AuthorType;
use SupportBay\Core\Events\Contracts\Listener;
use SupportBay\Modules\Activities\Enums\ActivityType;
use SupportBay\Modules\Activities\Services\ActivityService;

final class LogTicketSplitActivity implements Listener {
  public function __construct(private readonly ActivityService $activities) {}

  public function handle(object $event): void {
    $this->activities->create([
      'ticket_id' => $event->source()->id(),
      'actor_id' => $event->actorId(),
      'actor_type' => AuthorType::AGENT->value,
      'event_type' => ActivityType::TICKET_SPLIT->value,
      'description' => sprintf('Conversation entries split into ticket #%s.', $event->created()->trackId()),
      'payload' => ['created_ticket_id' => $event->created()->id()],
    ]);
    $this->activities->create([
      'ticket_id' => $event->created()->id(),
      'actor_id' => $event->actorId(),
      'actor_type' => AuthorType::AGENT->value,
      'event_type' => ActivityType::TICKET_SPLIT->value,
      'description' => sprintf('Split from ticket #%s.', $event->source()->trackId()),
      'payload' => ['source_ticket_id' => $event->source()->id()],
    ]);
  }
}
