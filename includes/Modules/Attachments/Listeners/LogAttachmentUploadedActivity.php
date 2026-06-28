<?php

declare(strict_types=1);

namespace SupportBay\Modules\Attachments\Listeners;

use SupportBay\Common\Enums\AuthorType;
use SupportBay\Core\Events\Contracts\Event;
use SupportBay\Core\Events\Contracts\Listener;
use SupportBay\Modules\Activities\Enums\ActivityType;
use SupportBay\Modules\Activities\Services\ActivityService;
use SupportBay\Modules\Attachments\Events\AttachmentUploaded;

final class LogAttachmentUploadedActivity implements Listener {
  public function __construct(
    private ActivityService $activities,
  ) {
  }

  /**
   * Handle event.
   */
  public function handle(Event $event): void {
    if (! $event instanceof AttachmentUploaded) {
      return;
    }

    $attachment = $event->attachment();

    $this->activities->create([
      'ticket_id' => $attachment->ticketId(),

      'actor_id' => $attachment->uploadedById(),

      'actor_type'  => $attachment->uploadedByType()->value,

      'event_type'  => ActivityType::ATTACHMENT_UPLOADED->value,

      'description' => sprintf(
        'Attachment "%s" uploaded.',
        $attachment->originalName()
      ),

      'payload' => [
        'attachment_id' => $attachment->id(),
        'message_id'    => $attachment->messageId(),
        'category'      => $attachment->category()->value,
        'file_name'     => $attachment->originalName(),
        'file_size'     => $attachment->fileSize(),
      ],
    ]);
  }
}
