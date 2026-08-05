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

    $userId = wp_insert_user([
      'user_login' => 'sbay-customer-' . strtolower(
        wp_generate_password(12, false, false)
      ),
      'user_pass'  => wp_generate_password(32, true, true),
      'role'       => 'subscriber',
    ]);

    Assert::true(
      is_int($userId) && $userId > 0,
      'Temporary WordPress user created.'
    );

    // -------------------------------------------------
    // Create Customer
    // -------------------------------------------------

    $customerId = $customerService->create([
      'user_id' => $userId,
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
      $userId,
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

    Assert::notNull(
      $customer->createdAt(),
      'Created timestamp generated.'
    );

    Assert::notNull(
      $customer->updatedAt(),
      'Updated timestamp generated.'
    );

    Assert::true(
      $customerService->deleteWithUser($customerId),
      'Test customer and WordPress user deleted.'
    );
  }
}
