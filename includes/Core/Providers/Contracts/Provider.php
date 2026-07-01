<?php

declare(strict_types=1);

namespace SupportBay\Core\Providers\Contracts;

interface Provider {
  /**
   * Unique provider identifier.
   *
   * Examples:
   * - envato
   * - edd
   * - woocommerce
   * - openai
   */
  public function slug(): string;

  /**
   * Human-readable provider name.
   */
  public function name(): string;

  /**
   * Provider category.
   *
   * Examples:
   * - marketplace
   * - ai
   * - notification
   * - payment
   * - storage
   * - other
   */
  public function category(): string;

  /**
   * Provider version.
   */
  public function version(): string;

  /**
   * Whether the provider is enabled.
   */
  public function isEnabled(): bool;

  /**
   * Enable the provider.
   */
  public function enable(): void;

  /**
   * Disable the provider.
   */
  public function disable(): void;

  /**
   * Provider configuration.
   *
   * Returns provider-specific settings.
   */
  public function settings(): array;

  /**
   * Update provider settings.
   *
   * @param array<string, mixed> $settings
   */
  public function setSettings(array $settings): void;

  /**
   * Boot the provider.
   *
   * Called after the provider has been registered.
   */
  public function boot(): void;
}
