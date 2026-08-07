<?php

declare(strict_types=1);

namespace SupportBay\Modules\Customers\Data;

use SupportBay\Modules\Customers\Entities\Customer;

final class CustomerProfileData {
  public function __construct(
    private readonly Customer $customer,
    private readonly string $displayName,
    private readonly string $email,
  ) {
  }

  public function customer(): Customer {
    return $this->customer;
  }

  public function displayName(): string {
    return $this->displayName;
  }

  public function email(): string {
    return $this->email;
  }
}
