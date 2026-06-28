<?php

declare(strict_types=1);

namespace SupportBay\Dev;

use SupportBay\Modules\Activities\Enums\ActivityType;
use SupportBay\Modules\Activities\Services\ActivityService;
use SupportBay\Modules\Attachments\Services\AttachmentService;
use SupportBay\Modules\Messages\Services\MessageService;
use SupportBay\Modules\Tickets\Services\TicketService;
use SupportBay\Common\Enums\AuthorType;
use SupportBay\Dev\Assert;

final class AttachmentFlowTest {
  public static function run(
    TicketService $ticketService,
    MessageService $messageService,
    AttachmentService $attachmentService,
    ActivityService $activityService,
  ): void {

    echo "<pre>";

    try {

      echo "🚀 Starting SupportBay Attachment Flow Test...\n\n";

      // -------------------------------------------------
      // Create Ticket
      // -------------------------------------------------

      $ticketId = $ticketService->create([
        'customer_id'   => 1,
        'department_id' => 1,
        'subject'       => 'Attachment Flow Test',
      ]);

      Assert::true(
        $ticketId > 0,
        'Ticket created.'
      );

      // -------------------------------------------------
      // Create Message
      // -------------------------------------------------

      $message = $messageService->create([
        'ticket_id'   => $ticketId,
        'author_id'   => 1,
        'author_type' => 'customer',
        'content'     => 'Uploading an attachment...',
      ]);

      Assert::notNull(
        $message,
        'Message created.'
      );

      Assert::true(
        $message->id() > 0,
        'Message ID generated.'
      );

      // -------------------------------------------------
      // Upload Attachment
      // -------------------------------------------------

      $attachmentId = $attachmentService->upload([
        'message_id'     => $message->id(),
        'ticket_id'      => $ticketId,
        'uploaded_by_id'    => 1,
        'uploaded_by_type' => AuthorType::CUSTOMER->value,
        'original_name'  => 'invoice.pdf',
        'path'           => 'uploads/supportbay/invoice.pdf',
        'file_size'      => 125480,
        'extension'      => 'pdf',
        'mime_type'      => 'application/pdf',
        'checksum'       => hash('sha256', 'invoice.pdf'),
        'is_previewable' => true,
      ]);

      Assert::true(
        $attachmentId > 0,
        'Attachment uploaded.'
      );

      // -------------------------------------------------
      // Verify Attachment
      // -------------------------------------------------

      $attachment = $attachmentService->find($attachmentId);

      Assert::notNull(
        $attachment,
        'Attachment retrieved.'
      );

      Assert::equals(
        'invoice.pdf',
        $attachment->originalName(),
        'Original filename stored.'
      );

      Assert::equals(
        'pdf',
        $attachment->category()->value,
        'Attachment category detected.'
      );

      Assert::equals(
        'active',
        $attachment->state()->value,
        'Attachment state is active.'
      );

      Assert::equals(
        'pending',
        $attachment->scanStatus()->value,
        'Scan status is pending.'
      );

      Assert::true(
        $attachment->canPreview(),
        'Attachment is previewable.'
      );

      // -------------------------------------------------
      // Verify Activity
      // -------------------------------------------------

      $activities = $activityService->getByTicket($ticketId);

      Assert::true(
        count($activities) > 0,
        'Activity timeline generated.'
      );

      $activityFound = false;

      foreach ($activities as $activity) {

        echo $activity->eventType()->value . PHP_EOL;

        if ($activity->eventType() === ActivityType::ATTACHMENT_UPLOADED) {

          $activityFound = true;

          Assert::equals(
            'customer',
            $activity->actorType()->value,
            'Activity actor is customer.'
          );

          break;
        }
      }

      Assert::true(
        $activityFound,
        'AttachmentUploaded activity recorded.'
      );

      echo "\n🎯 Attachment Flow Test Passed.\n";
    } catch (\Throwable $e) {

      echo "\n";
      echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
      echo "❌ TEST FAILED\n";
      echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
      echo $e->getMessage() . "\n";
      echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    }

    echo "</pre>";
  }
}
