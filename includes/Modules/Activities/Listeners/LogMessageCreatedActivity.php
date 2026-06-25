<?php

declare(strict_types=1);

namespace SupportBay\Modules\Activities\Listeners;

use SupportBay\Modules\Activities\Enums\ActivityType;
use SupportBay\Modules\Activities\Services\ActivityService;
use SupportBay\Modules\Messages\Events\MessageCreated;
use SupportBay\Core\Events\Contracts\Listener;

final class LogMessageCreatedActivity implements Listener {
  public function __construct(
    private ActivityService $activityService,
  ) {
  }

  /**
   * Handle event.
   */
  public function handle(object $event): void {
    $message = $event->message();

    $this->activityService->create([
      'ticket_id'   => $message->ticketId(),

      'actor_id'    => $message->authorId(),
      'actor_type'  => $message->authorType()->value,

      'event_type'  => ActivityType::MESSAGE_CREATED->value,

      'description' => $this->description($message),

      'payload'     => [
        'message_id' => $message->id(),
        'type'       => $message->type()->value,
      ],
    ]);
  }

  /**
   * Build activity description.
   */
  private function description($message): string {
    if ($message->isSystem()) {
      return 'System generated a message.';
    }

    if ($message->isFromCustomer()) {
      return 'Customer replied to ticket.';
    }

    return 'Staff replied to ticket.';
  }
}
