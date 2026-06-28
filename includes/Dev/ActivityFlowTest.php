<?php

declare(strict_types=1);

namespace SupportBay\Dev;

use SupportBay\Common\Enums\AuthorType;
use SupportBay\Core\Testing\Assert;
use SupportBay\Core\Testing\FlowTest;
use SupportBay\Modules\Activities\Enums\ActivityType;
use SupportBay\Modules\Activities\Services\ActivityService;
use SupportBay\Modules\Messages\Services\MessageService;
use SupportBay\Modules\Tickets\Services\TicketService;

final class ActivityFlowTest extends FlowTest {
  /**
   * Test title.
   */
  protected static function title(): string {
    return 'Activity Flow Test';
  }

  /**
   * Execute flow.
   */
  protected static function execute(...$services): void {
    [
      $ticketService,
      $messageService,
      $activityService,
    ] = $services;

    /** @var TicketService $ticketService */
    /** @var MessageService $messageService */
    /** @var ActivityService $activityService */

    echo "🚀 Starting SupportBay Activity Flow Test...\n\n";

    // -------------------------------------------------
    // Create Ticket
    // -------------------------------------------------

    $ticketId = $ticketService->create([
      'customer_id'   => 1,
      'department_id' => 1,
      'subject'       => 'Activity Flow Test',
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
      'author_type' => AuthorType::CUSTOMER->value,
      'content'     => 'Testing activity logging...',
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
    // Verify Activity Timeline
    // -------------------------------------------------

    $activities = $activityService->getByTicket($ticketId);

    Assert::true(
      count($activities) > 0,
      'Activity timeline generated.'
    );

    $messageActivity = null;

    foreach ($activities as $activity) {
      if ($activity->eventType() === ActivityType::MESSAGE_CREATED) {
        $messageActivity = $activity;
        break;
      }
    }

    Assert::notNull(
      $messageActivity,
      'MessageCreated activity recorded.'
    );

    Assert::equals(
      AuthorType::CUSTOMER,
      $messageActivity->actorType(),
      'Activity actor is customer.'
    );

    Assert::equals(
      ActivityType::MESSAGE_CREATED,
      $messageActivity->eventType(),
      'Activity type is message_created.'
    );

    Assert::equals(
      $ticketId,
      $messageActivity->ticketId(),
      'Activity belongs to ticket.'
    );

    Assert::true(
      $messageActivity->hasPayload(),
      'Activity payload stored.'
    );
  }
}
