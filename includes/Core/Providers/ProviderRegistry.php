<?php

declare(strict_types=1);

namespace SupportBay\Core\Providers;

use RuntimeException;
use SupportBay\Core\Providers\Contracts\IntegrationProvider;

final class ProviderRegistry {
  /**
   * Registered providers.
   *
   * @var array<string, Provider>
   */
  private array $providers = [];

  /**
   * Register a provider.
   */
  public function register(IntegrationProvider $provider): void {
    $slug = $provider->slug();

    if (isset($this->providers[$slug])) {
      throw new RuntimeException(
        sprintf(
          'Provider "%s" is already registered.',
          $slug
        )
      );
    }

    $this->providers[$slug] = $provider;
  }

  /**
   * Determine whether a provider exists.
   */
  public function has(string $slug): bool {
    return isset($this->providers[$slug]);
  }

  /**
   * Retrieve a provider.
   */
  public function get(string $slug): ?IntegrationProvider {
    return $this->providers[$slug] ?? null;
  }

  /**
   * Retrieve all providers.
   *
   * @return array<string, Provider>
   */
  public function all(): array {
    return $this->providers;
  }

  /**
   * Retrieve enabled providers.
   *
   * @return array<string, Provider>
   */
  public function enabled(): array {
    return array_filter(
      $this->providers,
      static fn(Provider $provider): bool => $provider->isEnabled()
    );
  }

  /**
   * Remove a provider.
   */
  public function unregister(string $slug): void {
    unset($this->providers[$slug]);
  }

  /**
   * Remove all providers.
   *
   * Mainly used by tests.
   */
  public function clear(): void {
    $this->providers = [];
  }

  /**
   * Boot all enabled providers.
   */
  public function boot(): void {
    foreach ($this->enabled() as $provider) {
      $provider->boot();
    }
  }
}
