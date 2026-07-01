<?php

declare(strict_types=1);

namespace SupportBay\Modules\Providers\Entities;

use SupportBay\Core\Entities\Entity;
use SupportBay\Modules\Providers\Enums\ProviderCategory;
use SupportBay\Modules\Providers\Enums\ProviderStatus;

final class Provider extends Entity {
  public function __construct(
    private int $id,
    private string $slug,
    private string $name,
    private ProviderCategory $category,
    private ?string $version,
    private ProviderStatus $status,
    private ?array $settings,
    private ?string $lastConnectedAt,
    private ?string $lastError,
    private ?array $metadata,
    private string $createdAt,
    private string $updatedAt,
  ) {
  }

  /**
   * Convert entity to array.
   */
  public function toArray(): array {
    return [
      'id'                => $this->id,
      'slug'              => $this->slug,
      'name'              => $this->name,
      'category'          => $this->category->value,
      'version'           => $this->version,
      'status'            => $this->status->value,
      'settings'          => $this->settings,
      'last_connected_at' => $this->lastConnectedAt,
      'last_error'        => $this->lastError,
      'metadata'          => $this->metadata,
      'created_at'        => $this->createdAt,
      'updated_at'        => $this->updatedAt,
    ];
  }

  /**
   * Provider ID.
   */
  public function id(): int {
    return $this->id;
  }

  /**
   * Provider slug.
   */
  public function slug(): string {
    return $this->slug;
  }

  /**
   * Provider name.
   */
  public function name(): string {
    return $this->name;
  }

  /**
   * Provider category.
   */
  public function category(): ProviderCategory {
    return $this->category;
  }

  /**
   * Provider version.
   */
  public function version(): ?string {
    return $this->version;
  }

  /**
   * Provider status.
   */
  public function status(): ProviderStatus {
    return $this->status;
  }

  /**
   * Provider settings.
   *
   * @return array<string, mixed>|null
   */
  public function settings(): ?array {
    return $this->settings;
  }

  /**
   * Last successful connection.
   */
  public function lastConnectedAt(): ?string {
    return $this->lastConnectedAt;
  }

  /**
   * Last connection error.
   */
  public function lastError(): ?string {
    return $this->lastError;
  }

  /**
   * Provider metadata.
   *
   * @return array<string, mixed>|null
   */
  public function metadata(): ?array {
    return $this->metadata;
  }

  /**
   * Creation timestamp.
   */
  public function createdAt(): string {
    return $this->createdAt;
  }

  /**
   * Last update timestamp.
   */
  public function updatedAt(): string {
    return $this->updatedAt;
  }

  /**
   * Determine whether the provider is enabled.
   */
  public function isEnabled(): bool {
    return $this->status->isEnabled();
  }

  /**
   * Determine whether the provider is disabled.
   */
  public function isDisabled(): bool {
    return $this->status->isDisabled();
  }

  /**
   * Determine whether the provider has settings.
   */
  public function hasSettings(): bool {
    return ! empty($this->settings);
  }

  /**
   * Determine whether the provider has a recorded connection error.
   */
  public function hasError(): bool {
    return ! empty($this->lastError);
  }

  /**
   * Determine whether the provider has connected successfully.
   */
  public function hasConnected(): bool {
    return ! empty($this->lastConnectedAt);
  }
}
