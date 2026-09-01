<?php

declare(strict_types=1);

namespace SupportBay\Providers\Envato\Services;

use SupportBay\Providers\Envato\Api\EnvatoApiClient;
use SupportBay\Providers\Envato\Exceptions\EnvatoException;

final class EnvatoCustomerService {
  /**
   * Customer endpoint.
   */
  private const ACCOUNT_ENDPOINT = '/v1/market/private/user/account.json';

  private const EMAIL_ENDPOINT = '/v1/market/private/user/email.json';

  private const USERNAME_ENDPOINT = '/v1/market/private/user/username.json';

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
  public function profile(string $accessToken): array {
    $emailResponse = [];

    try {
      $emailResponse = $this->client->get(
        self::EMAIL_ENDPOINT,
        $accessToken,
        authorizationOnly: true,
      );
    } catch (EnvatoException $exception) {
      $errorMessage = $exception->getMessage();
      throw new EnvatoException(
        'Envato could not retrieve the authenticated account email: ' . $errorMessage,
        0,
        $exception,
      );
    }

    $accountResponse = [];
    $usernameResponse = [];

    try {
      $accountResponse = $this->client->get(
        self::ACCOUNT_ENDPOINT,
        $accessToken,
        authorizationOnly: true,
      );
    } catch (EnvatoException) {
      // A valid Envato account can exist before its Marketplace profile does.
    }

    try {
      $usernameResponse = $this->client->get(
        self::USERNAME_ENDPOINT,
        $accessToken,
        authorizationOnly: true,
      );
    } catch (EnvatoException) {
      // Email remains a sufficient OAuth identity when Marketplace data is unavailable.
    }

    $account = isset($accountResponse['account']) && is_array($accountResponse['account'])
      ? $accountResponse['account']
      : $accountResponse;

    $account['email'] = $emailResponse['email'] ?? null;
    $account['username'] = $usernameResponse['username'] ?? null;

    return $account;
  }

  /**
   * Envato account identifier.
   */
  /** Username. */
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

  /** Display name. */
  public function displayName(array $customer): ?string {
    $name = trim(sprintf(
      '%s %s',
      (string) ($customer['firstname'] ?? ''),
      (string) ($customer['surname'] ?? ''),
    ));

    return $name !== ''
      ? $name
      : $this->username($customer);
  }

  /**
   * Account creation date.
   */
  public function registeredAt(array $customer): ?string {
    return $customer['created_at']
      ?? null;
  }
}
