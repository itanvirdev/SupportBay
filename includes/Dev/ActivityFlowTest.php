<?php

declare(strict_types=1);

namespace SupportBay\Dev;

use SupportBay\Common\Enums\AuthorType;
use SupportBay\Modules\Activities\Services\ActivityService;
use SupportBay\Modules\Messages\Enums\MessageType;
use SupportBay\Modules\Messages\Services\MessageService;
use SupportBay\Modules\Tickets\Services\TicketService;

final class ActivityFlowTest {
  public static function run(
    TicketService $ticketService,
    MessageService $messageService,
    ActivityService $activityService
  ): void {

    echo "🚀 Starting SupportBay Activity Flow Test...\n\n";

    /**
     * 1. Create Ticket
     */
    $ticketId = $ticketService->create([
      'subject'       => 'Activity Flow Test Ticket',
      'customer_id'   => 1,
      'department_id' => 1,
    ]);

    echo "✅ Ticket Created\n";
    echo "   Ticket ID: {$ticketId}\n\n";

    /**
     * 2. Create Message
     *
     * This should dispatch:
     * MessageCreated Event
     *
     * Which should trigger:
     * LogMessageCreatedActivity Listener
     */
    $message = $messageService->create([
      'ticket_id'   => $ticketId,
      'author_id'   => 1,
      'author_type' => AuthorType::CUSTOMER->value,
      'type'        => MessageType::REPLY->value,
      'content'     => 'Testing activity logging.',
    ]);

    echo "✅ Message Created\n";
    echo "   Message ID: {$message->id()}\n\n";

    /**
     * 3. Fetch Timeline
     */
    $activities = $activityService->getByTicket(
      $ticketId
    );

    echo "📋 Activity Timeline\n";
    echo "--------------------------\n";

    foreach ($activities as $activity) {

      echo "ID: {$activity->id()}\n";
      echo "Event: {$activity->eventType()->value}\n";
      echo "Actor: {$activity->actorType()->value}\n";
      echo "Description: {$activity->description()}\n";
      echo "Created: {$activity->createdAt()}\n";
      echo "--------------------------\n";
    }

    /**
     * 4. Verify Listener
     */
    $messageActivities = $activityService->getByEvent(
      \SupportBay\Modules\Activities\Enums\ActivityType::MESSAGE_CREATED
    );

    echo "\n🧠 Verification\n";

    if (count($messageActivities) > 0) {
      echo "✅ MessageCreated listener executed.\n";
      echo "✅ Activity record created.\n";
    } else {
      echo "❌ No activity record found.\n";
    }

    echo "\n🎯 Activity Flow Test Completed.\n";
  }
}
