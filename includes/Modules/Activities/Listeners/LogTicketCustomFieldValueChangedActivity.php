<?php

declare(strict_types=1);

namespace SupportBay\Modules\Activities\Listeners;

use SupportBay\Core\Events\Contracts\Listener;
use SupportBay\Modules\Activities\Enums\ActivityType;
use SupportBay\Modules\Activities\Services\ActivityService;
use SupportBay\Modules\CustomFields\Events\TicketCustomFieldValueChanged;

final class LogTicketCustomFieldValueChangedActivity implements Listener {
  public function __construct(private readonly ActivityService $activities) {}

  public function handle(object $event): void {
    if (! $event instanceof TicketCustomFieldValueChanged) { return; }
    $type = match ($event->action()) {
      'set' => ActivityType::CUSTOM_FIELD_SET,
      'cleared' => ActivityType::CUSTOM_FIELD_CLEARED,
      default => ActivityType::CUSTOM_FIELD_UPDATED,
    };
    $this->activities->create([
      'ticket_id' => $event->ticket()->id(),
      'actor_id' => $event->actorId(),
      'actor_type' => $event->actorType()->value,
      'event_type' => $type->value,
      'description' => sprintf('%s: %s.', $type->label(), $event->field()->name()),
      'payload' => [
        'field_id' => $event->field()->id(),
        'action' => $event->action(),
      ],
    ]);
  }
}
