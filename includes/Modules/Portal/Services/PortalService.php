<?php

declare(strict_types=1);

namespace SupportBay\Modules\Portal\Services;

use RuntimeException;
use SupportBay\Modules\Customers\Entities\Customer;
use SupportBay\Modules\Customers\Services\CustomerService;
use SupportBay\Modules\Tickets\Services\TicketService;
use SupportBay\Modules\Verifications\Services\VerificationService;

final class PortalService {
  public function __construct(
    private readonly CustomerService $customers,
    private readonly TicketService $tickets,
    private readonly VerificationService $verifications,
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
}
