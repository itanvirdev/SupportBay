<?php

declare(strict_types=1);

namespace SupportBay\Providers\Envato\Services;

use SupportBay\Providers\Envato\Api\EnvatoApiClient;

final class EnvatoOAuthService {
  /**
   * OAuth endpoints.
   */
  private const AUTHORIZE_URL = 'https://api.envato.com/authorization';

  private const TOKEN_ENDPOINT = '/token';

  private const USER_ENDPOINT = '/v1/market/private/user/account';

  /**
   * Constructor.
   */
  public function __construct(
    private readonly EnvatoApiClient $client,
  ) {
  }

  /**
   * Build the OAuth authorization URL.
   */
  public function authorizationUrl(
    string $clientId,
    string $redirectUri,
    string $state,
  ): string {

    return self::AUTHORIZE_URL . '?' . http_build_query([
      'response_type' => 'code',
      'client_id'     => $clientId,
      'redirect_uri'  => $redirectUri,
      'state'         => $state,
    ]);
  }

  /**
   * Exchange an authorization code for an access token.
   *
   * @return array<string, mixed>
   */
  public function exchangeCode(
    string $clientId,
    string $clientSecret,
    string $redirectUri,
    string $code,
  ): array {

    return $this->client->post(
      self::TOKEN_ENDPOINT,
      '',
      [
        'grant_type'    => 'authorization_code',
        'client_id'     => $clientId,
        'client_secret' => $clientSecret,
        'redirect_uri'  => $redirectUri,
        'code'          => $code,
      ]
    );
  }

  /**
   * Refresh an access token.
   *
   * @return array<string, mixed>
   */
  public function refreshToken(
    string $clientId,
    string $clientSecret,
    string $refreshToken,
  ): array {

    return $this->client->post(
      self::TOKEN_ENDPOINT,
      '',
      [
        'grant_type'    => 'refresh_token',
        'client_id'     => $clientId,
        'client_secret' => $clientSecret,
        'refresh_token' => $refreshToken,
      ]
    );
  }

  /**
   * Retrieve the authenticated Envato account.
   *
   * @return array<string, mixed>
   */
  public function account(string $accessToken): array {
    return $this->client->get(
      self::USER_ENDPOINT,
      $accessToken,
    );
  }
}
