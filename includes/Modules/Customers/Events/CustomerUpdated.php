<?php

declare(strict_types=1);

namespace SupportBay\Modules\Customers\Events;

use SupportBay\Core\Events\AbstractEvent;
use SupportBay\Modules\Customers\Entities\Customer;

final class CustomerUpdated extends AbstractEvent {
  public function __construct(
    private Customer $customer,
  ) {
  }

  public function customer(): Customer {
    return $this->customer;
  }
}
