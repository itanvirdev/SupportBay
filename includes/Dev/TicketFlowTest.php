<?php

declare(strict_types=1);

namespace SupportBay\Dev;

use SupportBay\Core\Testing\Assert;
use SupportBay\Core\Testing\FlowTest;
use SupportBay\Modules\Tickets\Enums\TicketPriority;
use SupportBay\Modules\Tickets\Enums\TicketStatus;
use SupportBay\Common\Enums\SourceType;
use SupportBay\Modules\Tickets\Services\TicketService;

final class TicketFlowTest extends FlowTest {
  /**
   * Test title.
   */
  protected static function title(): string {
    return 'Ticket Flow Test';
  }

  /**
   * Execute flow.
   */
  protected static function execute(...$services): void {
    /** @var TicketService $ticketService */
    [$ticketService] = $services;

    echo "🚀 Starting SupportBay Ticket Flow Test...\n\n";

    // -------------------------------------------------
    // Create Ticket
    // -------------------------------------------------

    $ticketId = $ticketService->create([
      'customer_id'   => 1,
      'department_id' => 1,
      'subject'       => 'Ticket Flow Test',
    ]);

    Assert::true(
      $ticketId > 0,
      'Ticket created.'
    );

    // -------------------------------------------------
    // Retrieve Ticket
    // -------------------------------------------------

    $ticket = $ticketService->find($ticketId);

    Assert::notNull(
      $ticket,
      'Ticket retrieved.'
    );

    Assert::equals(
      $ticketId,
      $ticket->id(),
      'Ticket ID matches.'
    );

    Assert::equals(
      'Ticket Flow Test',
      $ticket->subject(),
      'Subject stored.'
    );

    Assert::equals(
      1,
      $ticket->customerId(),
      'Customer assigned.'
    );

    Assert::equals(
      1,
      $ticket->departmentId(),
      'Department assigned.'
    );

    Assert::equals(
      TicketStatus::OPEN,
      $ticket->status(),
      'Default status applied.'
    );

    Assert::equals(
      TicketPriority::NORMAL,
      $ticket->priority(),
      'Default priority applied.'
    );

    Assert::equals(
      SourceType::WEB,
      $ticket->source(),
      'Default source applied.'
    );
  }
}
