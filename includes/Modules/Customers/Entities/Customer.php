<?php

declare(strict_types=1);

namespace SupportBay\Modules\Customers\Entities;

use SupportBay\Core\Entities\Entity;
use SupportBay\Modules\Customers\Enums\CustomerSource;
use SupportBay\Modules\Customers\Enums\CustomerState;

final class Customer extends Entity {
  public function __construct(
    private int $id,

    private int $userId,

    private CustomerState $state,

    private CustomerSource $source,

    private ?string $avatarUrl,

    private ?string $company,

    private ?string $phone,

    private ?string $country,

    private ?string $timezone,

    private ?string $language,

    private ?string $lastLoginAt,

    private ?string $metadata,

    private string $createdAt,

    private string $updatedAt,
  ) {
  }

  /**
   * Convert entity to array.
   */
  public function toArray(): array {
    return [
      'id'            => $this->id,
      'user_id'       => $this->userId,
      'state'         => $this->state->value,
      'source'        => $this->source->value,
      'avatar_url'    => $this->avatarUrl,
      'company'       => $this->company,
      'phone'         => $this->phone,
      'country'       => $this->country,
      'timezone'      => $this->timezone,
      'language'      => $this->language,
      'last_login_at' => $this->lastLoginAt,
      'metadata'      => $this->metadata,
      'created_at'    => $this->createdAt,
      'updated_at'    => $this->updatedAt,
    ];
  }

  // -----------------------------------------------------------------
  // Getters
  // -----------------------------------------------------------------

  public function id(): int {
    return $this->id;
  }

  public function userId(): int {
    return $this->userId;
  }

  public function state(): CustomerState {
    return $this->state;
  }

  public function source(): CustomerSource {
    return $this->source;
  }

  public function avatarUrl(): ?string {
    return $this->avatarUrl;
  }

  public function company(): ?string {
    return $this->company;
  }

  public function phone(): ?string {
    return $this->phone;
  }

  public function country(): ?string {
    return $this->country;
  }

  public function timezone(): ?string {
    return $this->timezone;
  }

  public function language(): ?string {
    return $this->language;
  }

  public function lastLoginAt(): ?string {
    return $this->lastLoginAt;
  }

  public function metadata(): ?string {
    return $this->metadata;
  }

  public function createdAt(): string {
    return $this->createdAt;
  }

  public function updatedAt(): string {
    return $this->updatedAt;
  }

  // -----------------------------------------------------------------
  // Domain Helpers
  // -----------------------------------------------------------------

  public function isGuest(): bool {
    return $this->state === CustomerState::GUEST;
  }

  public function isRegistered(): bool {
    return $this->state === CustomerState::REGISTERED;
  }

  public function isVerified(): bool {
    return $this->state === CustomerState::VERIFIED;
  }

  public function isSuspended(): bool {
    return $this->state === CustomerState::SUSPENDED;
  }

  public function cameFromProvider(): bool {
    return $this->source === CustomerSource::PROVIDER;
  }

  public function hasCompany(): bool {
    return ! empty($this->company);
  }

  public function hasPhone(): bool {
    return ! empty($this->phone);
  }

  public function hasAvatar(): bool {
    return ! empty($this->avatarUrl);
  }

  public function hasMetadata(): bool {
    return ! empty($this->metadata);
  }

  public function hasLoggedIn(): bool {
    return ! empty($this->lastLoginAt);
  }
}
