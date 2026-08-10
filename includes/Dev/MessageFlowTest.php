<?php

declare(strict_types=1);

namespace SupportBay\Dev;

use SupportBay\Common\Enums\AuthorType;
use SupportBay\Core\Testing\Assert;
use SupportBay\Core\Testing\FlowTest;
use SupportBay\Modules\Messages\Services\MessageService;
use SupportBay\Modules\Tickets\Services\TicketService;
use SupportBay\Modules\Messages\Enums\MessageType;

final class MessageFlowTest extends FlowTest {
  /**
   * Test title.
   */
  protected static function title(): string {
    return 'Message Flow Test';
  }

  /**
   * Execute flow.
   */
  protected static function execute(...$services): void {
    [
      $ticketService,
      $messageService,
    ] = $services;

    /** @var TicketService $ticketService */
    /** @var MessageService $messageService */

    echo "🚀 Starting SupportBay Message Flow Test...\n\n";

    // -------------------------------------------------
    // Create Ticket
    // -------------------------------------------------

    $ticketId = $ticketService->create([
      'customer_id'   => 1,
      'department_id' => 1,
      'subject'       => 'Message Flow Test',
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
      'content'     => 'This is a test message.',
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
    // Verify Message
    // -------------------------------------------------

    Assert::equals(
      $ticketId,
      $message->ticketId(),
      'Message belongs to ticket.'
    );

    Assert::equals(
      1,
      $message->authorId(),
      'Author ID stored.'
    );

    Assert::equals(
      AuthorType::CUSTOMER,
      $message->authorType(),
      'Author type stored.'
    );

    Assert::equals(
      'This is a test message.',
      $message->content(),
      'Message content stored.'
    );

    Assert::true(
      $message->createdAt() !== '',
      'Created timestamp generated.'
    );

    Assert::true(
      $ticketService->find($ticketId)->isUnassigned(),
      'Customer reply does not claim the ticket.'
    );

    $note = $messageService->create([
      'ticket_id' => $ticketId,
      'author_id' => 2,
      'author_type' => AuthorType::AGENT->value,
      'type' => MessageType::INTERNAL_NOTE->value,
      'content' => 'Internal context only.',
    ]);
    Assert::true(
      $ticketService->find($ticketId)->isUnassigned(),
      'Internal note does not claim the ticket.'
    );

    $firstReply = $messageService->create([
      'ticket_id' => $ticketId,
      'author_id' => 2,
      'author_type' => AuthorType::AGENT->value,
      'type' => MessageType::REPLY->value,
      'content' => 'First staff reply.',
    ]);
    Assert::equals(
      2,
      $ticketService->find($ticketId)->assignedAgentId(),
      'First public staff reply assigns the responder.'
    );

    $laterReply = $messageService->create([
      'ticket_id' => $ticketId,
      'author_id' => 3,
      'author_type' => AuthorType::MANAGER->value,
      'type' => MessageType::REPLY->value,
      'content' => 'Later staff reply.',
    ]);
    Assert::equals(
      2,
      $ticketService->find($ticketId)->assignedAgentId(),
      'Later replies do not replace the assigned responder.'
    );

    foreach ([$laterReply, $firstReply, $note, $message] as $createdMessage) {
      $messageService->delete($createdMessage->id());
    }
    $ticketService->delete($ticketId);
  }
}
