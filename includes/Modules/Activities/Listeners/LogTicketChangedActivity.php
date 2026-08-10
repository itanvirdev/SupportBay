<?php

declare(strict_types=1);

namespace SupportBay\Modules\Activities\Listeners;

use SupportBay\Common\Enums\AuthorType;
use SupportBay\Core\Events\Contracts\Listener;
use SupportBay\Modules\Activities\Enums\ActivityType;
use SupportBay\Modules\Activities\Services\ActivityService;

final class LogTicketChangedActivity implements Listener {
  public function __construct(private readonly ActivityService $activities) {}

  public function handle(object $event): void {
    $type = match ($event->change()) {
      'assignment' => $event->ticket()->isAssigned() ? ActivityType::TICKET_ASSIGNED : ActivityType::TICKET_UNASSIGNED,
      'department' => ActivityType::DEPARTMENT_CHANGED,
      'priority' => ActivityType::PRIORITY_CHANGED,
      default => ActivityType::STATE_CHANGED,
    };
    $this->activities->create([
      'ticket_id' => $event->ticket()->id(),
      'actor_id' => $event->actorId(),
      'actor_type' => AuthorType::AGENT->value,
      'event_type' => $type->value,
      'description' => $type->label() . '.',
    ]);
  }
}
