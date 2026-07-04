<?php

declare(strict_types=1);

namespace SupportBay\Providers\Envato\Data;

final readonly class EnvatoCustomer {
  /**
   * Constructor.
   */
  public function __construct(
    private int $id,
    private string $username,
    private ?string $email = null,
    private ?string $avatar = null,
    private ?string $country = null,
    private ?string $registeredAt = null,
  ) {
  }

  /**
   * Envato account ID.
   */
  public function id(): int {
    return $this->id;
  }

  /**
   * Envato username.
   */
  public function username(): string {
    return $this->username;
  }

  /**
   * Email address.
   *
   * May be unavailable depending on the API response.
   */
  public function email(): ?string {
    return $this->email;
  }

  /**
   * Avatar URL.
   */
  public function avatar(): ?string {
    return $this->avatar;
  }

  /**
   * Country.
   */
  public function country(): ?string {
    return $this->country;
  }

  /**
   * Registration timestamp.
   */
  public function registeredAt(): ?string {
    return $this->registeredAt;
  }

  /**
   * Convert DTO to array.
   *
   * @return array<string, mixed>
   */
  public function toArray(): array {
    return [
      'id'             => $this->id,
      'username'       => $this->username,
      'email'          => $this->email,
      'avatar'         => $this->avatar,
      'country'        => $this->country,
      'registered_at'  => $this->registeredAt,
    ];
  }
}
