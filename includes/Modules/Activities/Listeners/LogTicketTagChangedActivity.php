<?php

declare(strict_types=1);

namespace SupportBay\Modules\Activities\Listeners;

use SupportBay\Common\Enums\AuthorType;
use SupportBay\Core\Events\Contracts\Listener;
use SupportBay\Modules\Activities\Enums\ActivityType;
use SupportBay\Modules\Activities\Services\ActivityService;
use SupportBay\Modules\Tags\Events\TicketTagChanged;

final class LogTicketTagChangedActivity implements Listener {
  public function __construct(private readonly ActivityService $activities) {}

  public function handle(object $event): void {
    if (! $event instanceof TicketTagChanged) { return; }
    $type = $event->action() === 'added'
      ? ActivityType::TAG_ADDED
      : ActivityType::TAG_REMOVED;
    $this->activities->create([
      'ticket_id' => $event->ticket()->id(),
      'actor_id' => $event->actorId(),
      'actor_type' => AuthorType::AGENT->value,
      'event_type' => $type->value,
      'description' => sprintf('%s: %s.', $type->label(), $event->tag()->name()),
    ]);
  }
}
