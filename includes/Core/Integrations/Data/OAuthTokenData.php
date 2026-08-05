<?php

declare(strict_types=1);

namespace SupportBay\Core\Integrations\Data;

final readonly class OAuthTokenData {
  public function __construct(
    private string $accessToken,
    private ?string $refreshToken = null,
    private string $tokenType = 'Bearer',
    private ?int $expiresIn = null,
  ) {
  }

  public function accessToken(): string {
    return $this->accessToken;
  }

  public function refreshToken(): ?string {
    return $this->refreshToken;
  }

  public function tokenType(): string {
    return $this->tokenType;
  }

  public function expiresIn(): ?int {
    return $this->expiresIn;
  }

  /**
   * @return array<string, mixed>
   */
  public function toArray(): array {
    return [
      'access_token'  => $this->accessToken,
      'refresh_token' => $this->refreshToken,
      'token_type'    => $this->tokenType,
      'expires_in'    => $this->expiresIn,
    ];
  }
}
