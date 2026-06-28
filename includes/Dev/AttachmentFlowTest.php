<?php

declare(strict_types=1);

namespace SupportBay\Dev;

use SupportBay\Core\Testing\Assert;
use SupportBay\Core\Testing\FlowTest;
use SupportBay\Common\Enums\AuthorType;
use SupportBay\Modules\Activities\Enums\ActivityType;
use SupportBay\Modules\Activities\Services\ActivityService;
use SupportBay\Modules\Attachments\Enums\AttachmentCategory;
use SupportBay\Modules\Attachments\Enums\AttachmentState;
use SupportBay\Modules\Attachments\Enums\ScanStatus;
use SupportBay\Modules\Attachments\Services\AttachmentService;
use SupportBay\Modules\Messages\Services\MessageService;
use SupportBay\Modules\Tickets\Services\TicketService;

final class AttachmentFlowTest extends FlowTest {
  /**
   * Test title.
   */
  protected static function title(): string {
    return 'Attachment Flow Test';
  }

  /**
   * Execute flow.
   */
  protected static function execute(...$services): void {
    [
      $ticketService,
      $messageService,
      $attachmentService,
      $activityService,
    ] = $services;

    /** @var TicketService $ticketService */
    /** @var MessageService $messageService */
    /** @var AttachmentService $attachmentService */
    /** @var ActivityService $activityService */

    echo "🚀 Starting SupportBay Attachment Flow Test...\n\n";

    // -------------------------------------------------
    // Create Ticket
    // -------------------------------------------------

    $ticketId = $ticketService->create([
      'customer_id'   => 1,
      'department_id' => 1,
      'subject'       => 'Attachment Flow Test',
    ]);

    Assert::true($ticketId > 0, 'Ticket created.');

    // -------------------------------------------------
    // Create Message
    // -------------------------------------------------

    $message = $messageService->create([
      'ticket_id'   => $ticketId,
      'author_id'   => 1,
      'author_type' => AuthorType::CUSTOMER->value,
      'content'     => 'Uploading an attachment...',
    ]);

    Assert::notNull($message, 'Message created.');
    Assert::true($message->id() > 0, 'Message ID generated.');

    // -------------------------------------------------
    // Upload Attachment
    // -------------------------------------------------

    $attachmentId = $attachmentService->upload([
      'message_id'       => $message->id(),
      'ticket_id'        => $ticketId,

      'uploaded_by_id'   => 1,
      'uploaded_by_type' => AuthorType::CUSTOMER->value,

      'original_name'    => 'invoice.pdf',

      'path'             => 'uploads/supportbay/invoice.pdf',

      'file_size'        => 125480,

      'extension'        => 'pdf',

      'mime_type'        => 'application/pdf',

      'checksum'         => hash('sha256', 'invoice.pdf'),

      'is_previewable'   => true,
    ]);

    Assert::true($attachmentId > 0, 'Attachment uploaded.');

    // -------------------------------------------------
    // Verify Attachment
    // -------------------------------------------------

    $attachment = $attachmentService->find($attachmentId);

    Assert::notNull($attachment, 'Attachment retrieved.');

    Assert::equals(
      'invoice.pdf',
      $attachment->originalName(),
      'Original filename stored.'
    );

    Assert::equals(
      AttachmentCategory::PDF,
      $attachment->category(),
      'Attachment category detected.'
    );

    Assert::equals(
      AttachmentState::ACTIVE,
      $attachment->state(),
      'Attachment state is active.'
    );

    Assert::equals(
      ScanStatus::PENDING,
      $attachment->scanStatus(),
      'Scan status is pending.'
    );

    Assert::true(
      $attachment->canPreview(),
      'Attachment is previewable.'
    );

    // -------------------------------------------------
    // Verify Activities
    // -------------------------------------------------

    $activities = $activityService->getByTicket($ticketId);

    Assert::true(
      count($activities) >= 2,
      'Activity timeline generated.'
    );

    $attachmentActivity = null;

    foreach ($activities as $activity) {

      // echo $activity->eventType()->value . PHP_EOL;

      if ($activity->eventType() === ActivityType::ATTACHMENT_UPLOADED) {
        $attachmentActivity = $activity;
      }
    }

    Assert::notNull(
      $attachmentActivity,
      'AttachmentUploaded activity recorded.'
    );

    Assert::equals(
      AuthorType::CUSTOMER,
      $attachmentActivity->actorType(),
      'Activity actor is customer.'
    );
  }
}
