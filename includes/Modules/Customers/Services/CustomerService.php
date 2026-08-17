<?php

declare(strict_types=1);

namespace SupportBay\Modules\Customers\Services;

use InvalidArgumentException;
use RuntimeException;
use SupportBay\Core\Events\EventDispatcher;
use SupportBay\Core\Integrations\Data\OAuthLoginData;
use SupportBay\Core\Integrations\Data\OAuthTokenData;
use SupportBay\Modules\Customers\Data\CustomerProfileData;
use SupportBay\Modules\Customers\Entities\Customer;
use SupportBay\Modules\Customers\Enums\CustomerSource;
use SupportBay\Modules\Customers\Enums\CustomerState;
use SupportBay\Modules\Customers\Repositories\CustomerRepository;
use SupportBay\Modules\Customers\Repositories\WordPressUserRepository;
use SupportBay\Modules\Customers\Events\CustomerUpdated;

final class CustomerService {
  public function __construct(
    private readonly CustomerRepository $customers,
    private readonly WordPressUserRepository $users,
    private readonly EventDispatcher $events,
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

  public function ensureWordPressCustomer(
    int $userId,
    CustomerSource $source = CustomerSource::WORDPRESS,
  ): Customer {
    $customer = $this->findByUser($userId);

    if (! $customer) {
      $id = $this->create([
        'user_id' => $userId,
        'state' => CustomerState::REGISTERED->value,
        'source' => $source->value,
      ]);
      $customer = $this->find($id);
    }

    return $customer ?? throw new RuntimeException('The customer account could not be created.');
  }

  /** @return Customer[] */
  public function all(): array {
    return $this->customers->all();
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
   * Link a provider identity to an existing authenticated customer.
   */
  public function connectProvider(
    int $id,
    OAuthLoginData $login,
  ): Customer {
    $customer = $this->find($id);

    if (! $customer) {
      throw new RuntimeException('Customer not found.');
    }

    $identity = $login->identity();
    $linkedUserId = $this->users->findByProvider(
      $identity->provider(),
      $identity->providerReference(),
    );

    if (
      $linkedUserId !== null &&
      $linkedUserId !== $customer->userId()
    ) {
      throw new RuntimeException(
        'This provider account is already connected to another customer.'
      );
    }

    $this->users->linkProvider(
      $customer->userId(),
      $identity,
      $login->token(),
    );

    return $customer;
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
   * Load customer and linked WordPress identity profile data.
   */
  public function profile(int $id): CustomerProfileData {
    $customer = $this->find($id);

    if (! $customer) {
      throw new RuntimeException('Customer not found.');
    }

    $user = $this->users->find($customer->userId());

    if (! $user) {
      throw new RuntimeException('Customer account not found.');
    }

    return new CustomerProfileData(
      customer: $customer,
      displayName: (string) $user->display_name,
      email: (string) $user->user_email,
    );
  }

  /** @return array<int, array{provider: string, reference: string}> */
  public function providerConnections(int $id): array {
    $customer = $this->find($id);

    if (! $customer) {
      throw new RuntimeException('Customer not found.');
    }

    return $this->users->providerConnections($customer->userId());
  }

  /** @return array<string, mixed> */
  public function providerContext(int $id, string $provider): array {
    $customer = $this->find($id);

    if (! $customer) {
      throw new RuntimeException('Customer not found.');
    }

    $connection = $this->users->providerConnection(
      $customer->userId(),
      sanitize_key($provider),
    );
    $token = $connection['token'] ?? [];

    return is_array($token) ? $token : [];
  }

  public function updateProviderToken(
    int $id,
    string $provider,
    OAuthTokenData $token,
  ): void {
    $customer = $this->find($id);

    if (! $customer) {
      throw new RuntimeException('Customer not found.');
    }

    $this->users->updateProviderToken(
      $customer->userId(),
      sanitize_key($provider),
      $token,
    );
  }

  /**
   * Update customer-editable profile fields.
   *
   * @param array<string, mixed> $data
   */
  public function updateProfile(
    int $id,
    array $data,
  ): CustomerProfileData {
    $this->profile($id);
    $allowed = [
      'company'  => 150,
      'phone'    => 50,
      'country'  => 100,
      'timezone' => 100,
      'language' => 20,
    ];
    $updates = [];

    foreach ($allowed as $field => $maximumLength) {
      if (! array_key_exists($field, $data)) {
        continue;
      }

      $value = trim((string) $data[$field]);

      $length = function_exists('mb_strlen')
        ? mb_strlen($value)
        : strlen($value);

      if ($length > $maximumLength) {
        throw new InvalidArgumentException(
          sprintf('%s is too long.', ucfirst($field))
        );
      }

      $updates[$field] = $value !== '' ? $value : null;
    }

    if (
      isset($updates['timezone']) &&
      ! in_array($updates['timezone'], timezone_identifiers_list(), true)
    ) {
      throw new InvalidArgumentException('Please select a valid timezone.');
    }

    if (
      isset($updates['language']) &&
      ! preg_match('/^[A-Za-z]{2,3}(?:[_-][A-Za-z]{2})?$/', $updates['language'])
    ) {
      throw new InvalidArgumentException('Please enter a valid language code.');
    }

    if ($updates === []) {
      throw new InvalidArgumentException('No profile changes were provided.');
    }

    $this->customers->update($id, $updates);
    $profile = $this->profile($id);
    $this->events->dispatch(
      new CustomerUpdated($profile->customer())
    );

    return $profile;
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
