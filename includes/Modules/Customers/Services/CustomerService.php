<?php

declare(strict_types=1);

namespace SupportBay\Modules\Customers\Services;

use RuntimeException;
use SupportBay\Core\Integrations\Data\OAuthLoginData;
use SupportBay\Modules\Customers\Entities\Customer;
use SupportBay\Modules\Customers\Enums\CustomerSource;
use SupportBay\Modules\Customers\Enums\CustomerState;
use SupportBay\Modules\Customers\Repositories\CustomerRepository;
use SupportBay\Modules\Customers\Repositories\WordPressUserRepository;

final class CustomerService {
  public function __construct(
    private readonly CustomerRepository $customers,
    private readonly WordPressUserRepository $users,
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
    return $this->customers->findByUserId($userId);
  }

  /**
   * Get customers by state.
   *
   * @return Customer[]
   */
  public function getByState(CustomerState $state): array {
    return $this->customers->findByState($state);
  }

  /**
   * Get customers by source.
   *
   * @return Customer[]
   */
  public function getBySource(CustomerSource $source): array {
    return $this->customers->findBySource($source);
  }

  /**
   * Create or update a customer from normalized OAuth data.
   */
  public function linkProvider(OAuthLoginData $login): Customer {
    $identity = $login->identity();

    $userId = $this->users->findByProvider(
      $identity->provider(),
      $identity->providerReference(),
    );

    if ($userId === null && $identity->email() !== null) {
      $userId = $this->users->findByEmail($identity->email());
    }

    if ($userId === null) {
      $userId = $this->users->create($identity);
    }

    $this->users->linkProvider(
      $userId,
      $identity,
      $login->token(),
    );

    $customer = $this->findByUser($userId);

    if (! $customer) {
      $customerId = $this->create([
        'user_id'    => $userId,
        'state'      => CustomerState::REGISTERED->value,
        'source'     => CustomerSource::PROVIDER->value,
        'avatar_url' => $identity->avatarUrl(),
        'country'    => $identity->country(),
        'metadata'   => wp_json_encode([
          'provider'           => $identity->provider(),
          'provider_reference' => $identity->providerReference(),
          'provider_username'  => $identity->username(),
        ]),
      ]);

      $customer = $this->find($customerId);
    }

    if (! $customer) {
      throw new RuntimeException(
        'Unable to load the linked customer.'
      );
    }

    $this->recordLogin($customer->id());

    return $this->find($customer->id()) ?? $customer;
  }

  /**
   * Delete a customer and its WordPress user.
   */
  public function deleteWithUser(int $id): bool {
    $customer = $this->find($id);

    if (! $customer) {
      return false;
    }

    $userId = $customer->userId();

    return $this->customers->delete($id)
      && $this->users->delete($userId);
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
