<?php

declare(strict_types=1);

namespace SupportBay\Providers\Envato\Services;

use SupportBay\Providers\Envato\Api\EnvatoApiClient;

final class EnvatoCustomerService {
  /**
   * Customer endpoint.
   */
  private const ACCOUNT_ENDPOINT = '/v1/market/private/user/account';

  /**
   * Constructor.
   */
  public function __construct(
    private readonly EnvatoApiClient $client,
  ) {
  }

  /**
   * Retrieve the authenticated Envato customer.
   *
   * @return array<string, mixed>
   */
  public function account(string $accessToken): array {
    return $this->client->get(
      self::ACCOUNT_ENDPOINT,
      $accessToken,
    );
  }

  /**
   * Envato account identifier.
   */
  public function id(array $customer): ?int {
    return isset($customer['id'])
      ? (int) $customer['id']
      : null;
  }

  /**
   * Username.
   */
  public function username(array $customer): ?string {
    return $customer['username']
      ?? null;
  }

  /**
   * Email address.
   *
   * Not always available from Envato.
   */
  public function email(array $customer): ?string {
    return $customer['email']
      ?? null;
  }

  /**
   * Avatar URL.
   */
  public function avatar(array $customer): ?string {
    return $customer['image']
      ?? null;
  }

  /**
   * Country.
   */
  public function country(array $customer): ?string {
    return $customer['country']
      ?? null;
  }

  /**
   * Account creation date.
   */
  public function registeredAt(array $customer): ?string {
    return $customer['created_at']
      ?? null;
  }
}
