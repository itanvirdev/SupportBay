<?php

declare(strict_types=1);

namespace SupportBay\Core\Integrations\Data;

final readonly class OAuthIdentityData {
  public function __construct(
    private string $provider,
    private string $providerReference,
    private string $username,
    private ?string $email = null,
    private ?string $displayName = null,
    private ?string $avatarUrl = null,
    private ?string $country = null,
    private array $snapshot = [],
  ) {
  }

  public function provider(): string {
    return $this->provider;
  }

  public function providerReference(): string {
    return $this->providerReference;
  }

  public function username(): string {
    return $this->username;
  }

  public function email(): ?string {
    return $this->email;
  }

  public function displayName(): ?string {
    return $this->displayName;
  }

  public function avatarUrl(): ?string {
    return $this->avatarUrl;
  }

  public function country(): ?string {
    return $this->country;
  }

  /**
   * @return array<string, mixed>
   */
  public function snapshot(): array {
    return $this->snapshot;
  }
}
