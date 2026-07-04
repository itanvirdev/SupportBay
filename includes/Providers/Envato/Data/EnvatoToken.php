<?php

declare(strict_types=1);

namespace SupportBay\Providers\Envato\Data;

final readonly class EnvatoToken {
  /**
   * Constructor.
   */
  public function __construct(
    private string $accessToken,
    private ?string $refreshToken = null,
    private ?string $tokenType = 'Bearer',
    private ?int $expiresIn = null,
    private ?string $expiresAt = null,
  ) {
  }

  /**
   * Access token.
   */
  public function accessToken(): string {
    return $this->accessToken;
  }

  /**
   * Refresh token.
   */
  public function refreshToken(): ?string {
    return $this->refreshToken;
  }

  /**
   * Token type.
   */
  public function tokenType(): ?string {
    return $this->tokenType;
  }

  /**
   * Token lifetime (seconds).
   */
  public function expiresIn(): ?int {
    return $this->expiresIn;
  }

  /**
   * Token expiration timestamp.
   */
  public function expiresAt(): ?string {
    return $this->expiresAt;
  }

  /**
   * Determine whether the token has expired.
   */
  public function isExpired(): bool {
    if ($this->expiresAt === null) {
      return false;
    }

    return strtotime($this->expiresAt) <= time();
  }

  /**
   * Convert DTO to array.
   *
   * @return array<string, mixed>
   */
  public function toArray(): array {
    return [
      'access_token'  => $this->accessToken,
      'refresh_token' => $this->refreshToken,
      'token_type'    => $this->tokenType,
      'expires_in'    => $this->expiresIn,
      'expires_at'    => $this->expiresAt,
    ];
  }
}
