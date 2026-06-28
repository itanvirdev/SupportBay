<?php

declare(strict_types=1);

namespace SupportBay\Modules\Customers\Repositories;

use SupportBay\Core\Database\Repository;
use SupportBay\Modules\Customers\Database\CustomerSchema;
use SupportBay\Modules\Customers\Entities\Customer;
use SupportBay\Modules\Customers\Enums\CustomerSource;
use SupportBay\Modules\Customers\Enums\CustomerState;

final class CustomerRepository extends Repository {
  /**
   * Database table.
   */
  protected function table(): string {
    return CustomerSchema::tableName();
  }

  /**
   * Hydrate entity.
   */
  protected function hydrate(array $row): Customer {
    return new Customer(
      id: (int) $row['id'],

      userId: (int) $row['user_id'],

      state: CustomerState::from($row['state']),

      source: CustomerSource::from($row['source']),

      avatarUrl: $row['avatar_url'] ?: null,

      company: $row['company'] ?: null,

      phone: $row['phone'] ?: null,

      country: $row['country'] ?: null,

      timezone: $row['timezone'] ?: null,

      language: $row['language'] ?: null,

      lastLoginAt: $row['last_login_at'] ?: null,

      metadata: $row['metadata'] ?: null,

      createdAt: $row['created_at'],

      updatedAt: $row['updated_at'],
    );
  }

  /**
   * Find by WordPress user.
   */
  public function findByUserId(int $userId): ?Customer {
    return $this->first([
      'user_id' => $userId,
    ]);
  }

  /**
   * Find customers by state.
   *
   * @return Customer[]
   */
  public function findByState(CustomerState $state): array {
    return $this->findWhere([
      'state' => $state->value,
    ]);
  }

  /**
   * Find customers by source.
   *
   * @return Customer[]
   */
  public function findBySource(CustomerSource $source): array {
    return $this->findWhere([
      'source' => $source->value,
    ]);
  }

  /**
   * Customer exists for WP user.
   */
  public function existsForUser(int $userId): bool {
    return $this->exists([
      'user_id' => $userId,
    ]);
  }

  /**
   * Record last login.
   */
  public function touchLastLogin(int $id): bool {
    return $this->updateById($id, [
      'last_login_at' => $this->now(),
      'updated_at'    => $this->now(),
    ]);
  }
}
