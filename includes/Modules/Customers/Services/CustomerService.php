<?php

declare(strict_types=1);

namespace SupportBay\Modules\Customers\Services;

use RuntimeException;
use SupportBay\Modules\Customers\Entities\Customer;
use SupportBay\Modules\Customers\Enums\CustomerSource;
use SupportBay\Modules\Customers\Enums\CustomerState;
use SupportBay\Modules\Customers\Repositories\CustomerRepository;

final class CustomerService {
  public function __construct(
    private CustomerRepository $customers,
  ) {
  }

  /**
   * Create customer.
   */
  public function create(array $data): int {
    $data = $this->normalize($data);

    return $this->customers->create($data);
  }

  /**
   * Find customer.
   */
  public function find(int $id): ?Customer {
    return $this->customers->find($id);
  }

  /**
   * Find customer by WordPress user.
   */
  public function findByUser(int $userId): ?Customer {
    return $this->customers->findByUser($userId);
  }

  /**
   * Get customers by state.
   *
   * @return Customer[]
   */
  public function getByState(CustomerState $state): array {
    return $this->customers->getByState($state);
  }

  /**
   * Get customers by source.
   *
   * @return Customer[]
   */
  public function getBySource(CustomerSource $source): array {
    return $this->customers->getBySource($source);
  }

  /**
   * Mark customer as registered.
   */
  public function register(int $id): bool {
    return $this->updateState(
      $id,
      CustomerState::REGISTERED
    );
  }

  /**
   * Verify customer.
   */
  public function verify(int $id): bool {
    return $this->updateState(
      $id,
      CustomerState::VERIFIED
    );
  }

  /**
   * Suspend customer.
   */
  public function suspend(int $id): bool {
    return $this->updateState(
      $id,
      CustomerState::SUSPENDED
    );
  }

  /**
   * Restore customer.
   */
  public function restore(int $id): bool {
    return $this->updateState(
      $id,
      CustomerState::REGISTERED
    );
  }

  /**
   * Update last login.
   */
  public function recordLogin(int $id): bool {
    return $this->customers->update($id, [
      'last_login_at' => current_time('mysql'),
    ]);
  }

  /**
   * Update customer metadata.
   */
  public function update(int $id, array $data): bool {
    return $this->customers->update($id, $data);
  }

  /**
   * Update customer state.
   */
  private function updateState(
    int $id,
    CustomerState $state,
  ): bool {
    if (! $this->find($id)) {
      throw new RuntimeException(
        'Customer not found.'
      );
    }

    return $this->customers->update($id, [
      'state' => $state->value,
    ]);
  }

  /**
   * Apply defaults.
   */
  private function normalize(array $data): array {
    $data['state'] ??= CustomerState::default()->value;

    $data['source'] ??= CustomerSource::default()->value;

    return $data;
  }
}
