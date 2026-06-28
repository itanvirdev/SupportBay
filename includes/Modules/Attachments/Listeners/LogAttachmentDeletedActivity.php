<?php

declare(strict_types=1);

namespace SupportBay\Modules\Attachments\Listeners;

use SupportBay\Common\Enums\AuthorType;
use SupportBay\Core\Events\Contracts\Event;
use SupportBay\Core\Events\Contracts\Listener;
use SupportBay\Modules\Activities\Enums\ActivityType;
use SupportBay\Modules\Activities\Services\ActivityService;
use SupportBay\Modules\Attachments\Events\AttachmentDeleted;

final class LogAttachmentDeletedActivity implements Listener {
  public function __construct(
    private readonly ActivityService $activities,
  ) {
  }

  /**
   * Handle event.
   */
  public function handle(Event $event): void {
    if (! $event instanceof AttachmentDeleted) {
      return;
    }

    $attachment = $event->attachment();

    $this->activities->create([
      'ticket_id' => $attachment->ticketId(),

      'actor_id' => $attachment->uploadedById(),

      // TODO:
      // Replace with uploadedByType() once the Attachment
      // entity supports the actor model.
      'actor_type'  => $attachment->uploadedByType()->value,

      'event_type'  => ActivityType::ATTACHMENT_UPLOADED->value,

      'description' => sprintf(
        'Attachment "%s" deleted.',
        $attachment->originalName()
      ),

      'payload' => [
        'attachment_id' => $attachment->id(),
        'message_id'    => $attachment->messageId(),
        'file_name'     => $attachment->originalName(),
        'category'      => $attachment->category()->value,
        'stored_name'   => $attachment->storedName(),
      ],
    ]);
  }
}
