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

  /** @param array<string, mixed> $data */
  public static function fromArray(array $data): self {
    return new self(
      accessToken: sanitize_text_field(
        (string) ($data['access_token'] ?? '')
      ),
      refreshToken: self::nullableString(
        $data['refresh_token'] ?? null
      ),
      tokenType: sanitize_text_field(
        (string) ($data['token_type'] ?? 'Bearer')
      ),
      expiresIn: isset($data['expires_in'])
        ? max(0, (int) $data['expires_in'])
        : null,
    );
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

  private static function nullableString(mixed $value): ?string {
    $value = is_scalar($value)
      ? sanitize_text_field((string) $value)
      : '';

    return $value !== '' ? $value : null;
  }
}
