<?php

declare(strict_types=1);

namespace SupportBay\Modules\Attachments\Events;

use SupportBay\Core\Events\AbstractEvent;
use SupportBay\Modules\Attachments\Entities\Attachment;

final class AttachmentUploaded extends AbstractEvent {
  public function __construct(
    private readonly Attachment $attachment,
  ) {
    parent::__construct();
  }

  /**
   * Get uploaded attachment.
   */
  public function attachment(): Attachment {
    return $this->attachment;
  }

  /**
   * Event payload.
   */
  public function payload(): array {
    return [
      'attachment_id' => $this->attachment->id(),
      'message_id'    => $this->attachment->messageId(),
      'ticket_id'     => $this->attachment->ticketId(),
      'uploaded_by_id'   => $this->attachment->uploadedById(),
      'category'      => $this->attachment->category()->value,
      'disk'          => $this->attachment->disk()->value,
      'file_size'     => $this->attachment->fileSize(),
      'mime_type'     => $this->attachment->mimeType(),
    ];
  }
}
