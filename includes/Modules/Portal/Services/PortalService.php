<?php

declare(strict_types=1);

namespace SupportBay\Modules\Portal\Services;

use RuntimeException;
use SupportBay\Modules\Customers\Entities\Customer;
use SupportBay\Modules\Customers\Services\CustomerService;
use SupportBay\Modules\Messages\Entities\Message;
use SupportBay\Modules\Messages\Services\MessageService;
use SupportBay\Modules\Tickets\Entities\Ticket;
use SupportBay\Modules\Tickets\Services\TicketService;
use SupportBay\Modules\Verifications\Services\VerificationService;
use SupportBay\Modules\Verifications\Entities\Verification;

final class PortalService {
  public function __construct(
    private readonly CustomerService $customers,
    private readonly TicketService $tickets,
    private readonly VerificationService $verifications,
    private readonly MessageService $messages,
  ) {
  }

  /**
   * Resolve the authenticated SupportBay customer.
   */
  public function currentCustomer(): Customer {
    $userId = get_current_user_id();

    if ($userId <= 0) {
      throw new RuntimeException(
        'Authentication is required.'
      );
    }

    $customer = $this->customers->findByUser($userId);

    if (! $customer) {
      throw new RuntimeException(
        'The authenticated user is not a SupportBay customer.'
      );
    }

    return $customer;
  }

  /**
   * Get the current customer's tickets.
   *
   * @return array<int, \SupportBay\Modules\Tickets\Entities\Ticket>
   */
  public function tickets(): array {
    return $this->tickets->findByCustomer(
      $this->currentCustomer()->id()
    );
  }

  /**
   * Get the current customer's verifications.
   *
   * @return array<int, \SupportBay\Modules\Verifications\Entities\Verification>
   */
  public function verifications(): array {
    return $this->verifications->findByCustomer(
      $this->currentCustomer()->id()
    );
  }

  /**
   * Resolve a current-customer verification by ID.
   */
  public function verification(
    int $verificationId,
  ): ?Verification {
    foreach ($this->verifications() as $verification) {
      if ($verification->id() === $verificationId) {
        return $verification;
      }
    }

    return null;
  }

  /**
   * Resolve a ticket owned by the current customer.
   */
  public function ticket(int $ticketId): Ticket {
    $ticket = $this->tickets->find($ticketId);
    $customer = $this->currentCustomer();

    if (! $ticket || $ticket->customerId() !== $customer->id()) {
      throw new RuntimeException(
        'Ticket was not found.'
      );
    }

    return $ticket;
  }

  /**
   * Get customer-visible messages for an owned ticket.
   *
   * @return Message[]
   */
  public function ticketMessages(int $ticketId): array {
    $this->ticket($ticketId);

    return array_values(array_filter(
      $this->messages->findByTicket($ticketId),
      fn(Message $message): bool => $message->isVisibleToCustomer(),
    ));
  }
}
