<?php

declare(strict_types=1);

namespace SupportBay\Dev;

use SupportBay\Modules\Tickets\Services\TicketService;
use SupportBay\Modules\Messages\Services\MessageService;
use SupportBay\Modules\Messages\Enums\MessageType;
use SupportBay\Common\Enums\AuthorType;

final class MessageFlowTest {
  public static function run(
    TicketService $ticketService,
    MessageService $messageService
  ): void {

    echo "🚀 Starting SupportBay Message Flow Test...\n\n";

    /**
     * 1. Create Ticket
     */
    $ticketId = $ticketService->create([
      'subject' => 'Flow Test Ticket',
      'customer_id' => 1,
      'department_id' => 1,
    ]);

    echo "✅ Ticket created: ID {$ticketId}\n";

    /**
     * 2. Create Message (Customer Reply)
     */
    $message = $messageService->create([
      'ticket_id'    => $ticketId,
      'author_id'    => 1,
      'author_type'  => AuthorType::CUSTOMER->value,
      'type'         => MessageType::REPLY->value,
      'content'      => 'Hello, this is a test message from customer.',
    ]);

    echo "✅ Message created: ID {$message->id()}\n";
    echo "   Type: {$message->type()->value}\n";
    echo "   Author: {$message->authorType()->value}\n";

    /**
     * 3. Verify Ticket Sync
     */
    global $wpdb;

    $ticket = $wpdb->get_row(
      $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}sbay_tickets WHERE id = %d",
        $ticketId
      ),
      ARRAY_A
    );

    echo "\n📦 Ticket Sync Check:\n";
    echo "   Last Message ID: " . $ticket['last_message_id'] . "\n";
    echo "   Last Reply At: " . $ticket['last_reply_at'] . "\n";

    /**
     * 4. Verify Message Entity Behavior
     */
    echo "\n🧠 Entity Checks:\n";

    echo "   Is From Customer: " . ($message->isFromCustomer() ? 'YES' : 'NO') . "\n";
    echo "   Is System: " . ($message->isSystem() ? 'YES' : 'NO') . "\n";
    echo "   Is Read By Customer: " . ($message->isReadByCustomer() ? 'YES' : 'NO') . "\n";

    echo "\n🎯 Flow Test Completed Successfully.\n";
  }
}
