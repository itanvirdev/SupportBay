<?php

declare(strict_types=1);

namespace SupportBay\Dev;

use SupportBay\Core\Testing\Assert;
use SupportBay\Core\Testing\FlowTest;
use SupportBay\Modules\Customers\Enums\CustomerSource;
use SupportBay\Modules\Customers\Enums\CustomerState;
use SupportBay\Modules\Customers\Services\CustomerService;

final class CustomerFlowTest extends FlowTest {
  /**
   * Test title.
   */
  protected static function title(): string {
    return 'Customer Flow Test';
  }

  /**
   * Execute flow.
   */
  protected static function execute(...$services): void {
    /** @var CustomerService $customerService */
    [$customerService] = $services;

    echo "🚀 Starting SupportBay Customer Flow Test...\n\n";

    // -------------------------------------------------
    // Create Customer
    // -------------------------------------------------

    $customerId = $customerService->create([
      'user_id' => 1,
      'state'   => CustomerState::REGISTERED->value,
      'source'  => CustomerSource::REGISTRATION->value,
    ]);

    Assert::true(
      $customerId > 0,
      'Customer created.'
    );

    // -------------------------------------------------
    // Retrieve Customer
    // -------------------------------------------------

    $customer = $customerService->find($customerId);

    Assert::notNull(
      $customer,
      'Customer retrieved.'
    );

    Assert::equals(
      $customerId,
      $customer->id(),
      'Customer ID matches.'
    );

    Assert::equals(
      1,
      $customer->userId(),
      'WordPress user linked.'
    );

    Assert::equals(
      CustomerState::REGISTERED,
      $customer->state(),
      'Customer state stored.'
    );

    Assert::equals(
      CustomerSource::REGISTRATION,
      $customer->source(),
      'Customer source stored.'
    );

    Assert::equals(
      null,
      $customer->lastLoginAt(),
      'Last login is empty.'
    );

    Assert::notEmpty(
      $customer->createdAt(),
      'Created timestamp generated.'
    );

    Assert::notEmpty(
      $customer->updatedAt(),
      'Updated timestamp generated.'
    );
  }
}
