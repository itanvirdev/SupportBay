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

    $suffix = strtolower(wp_generate_password(8, false, false));
    $agentId = wp_create_user(
      'sbay-message-agent-' . $suffix,
      wp_generate_password(24),
      'message-agent-' . $suffix . '@example.test',
    );
    $managerId = wp_create_user(
      'sbay-message-manager-' . $suffix,
      wp_generate_password(24),
      'message-manager-' . $suffix . '@example.test',
    );
    (new \WP_User($agentId))->set_role('sbay_agent');
    (new \WP_User($managerId))->set_role('sbay_manager');

    // -------------------------------------------------
    // Create Ticket
    // -------------------------------------------------

    $ticketId = $ticketService->create([
      'customer_id'   => 1,
      'subject'       => 'Message Flow Test',
    ]);

    Assert::true(
      $ticketId > 0,
      'Ticket created.'
    );

    $ticketService->changeAssignment($ticketId, null, $managerId);

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
      'author_id' => $agentId,
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
      'author_id' => $agentId,
      'author_type' => AuthorType::AGENT->value,
      'type' => MessageType::REPLY->value,
      'content' => 'First staff reply.',
    ]);
    Assert::equals(
      $agentId,
      $ticketService->find($ticketId)->assignedAgentId(),
      'First public staff reply assigns the responder.'
    );

    $laterReply = $messageService->create([
      'ticket_id' => $ticketId,
      'author_id' => $managerId,
      'author_type' => AuthorType::MANAGER->value,
      'type' => MessageType::REPLY->value,
      'content' => 'Later staff reply.',
    ]);
    Assert::equals(
      $agentId,
      $ticketService->find($ticketId)->assignedAgentId(),
      'Later replies do not replace the assigned responder.'
    );

    Assert::equals(
      AuthorType::MANAGER,
      $messageService->latestReplyAuthorTypes([$ticketId])[$ticketId] ?? null,
      'Latest public reply author identifies a support response for the customer queue.',
    );

    $customerReply = $messageService->create([
      'ticket_id' => $ticketId,
      'author_id' => 1,
      'author_type' => AuthorType::CUSTOMER->value,
      'type' => MessageType::REPLY->value,
      'content' => 'Customer follow-up.',
    ]);
    Assert::equals(
      AuthorType::CUSTOMER,
      $messageService->latestReplyAuthorTypes([$ticketId])[$ticketId] ?? null,
      'Customer follow-up clears the support-replied queue state.',
    );

    foreach ([$customerReply, $laterReply, $firstReply, $note, $message] as $createdMessage) {
      $messageService->delete($createdMessage->id());
    }
    $ticketService->delete($ticketId);
    wp_delete_user($agentId);
    wp_delete_user($managerId);
  }
}
