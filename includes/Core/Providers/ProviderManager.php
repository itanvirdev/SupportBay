<?php

declare(strict_types=1);

namespace SupportBay\Core\Providers;

use RuntimeException;
use SupportBay\Core\Providers\Contracts\IntegrationProvider;

final class ProviderManager {
  /**
   * Provider registry.
   */
  public function __construct(
    private readonly ProviderRegistry $registry,
  ) {
  }

  /**
   * Register a provider.
   */
  public function register(IntegrationProvider $provider): void {
    $this->registry->register($provider);
  }

  /**
   * Determine whether a provider exists.
   */
  public function has(string $slug): bool {
    return $this->registry->has($slug);
  }

  /**
   * Get a provider.
   *
   * @throws RuntimeException
   */
  public function provider(string $slug): IntegrationProvider {
    $provider = $this->registry->get($slug);

    if (! $provider) {
      throw new RuntimeException(
        sprintf(
          'Provider "%s" is not registered.',
          $slug
        )
      );
    }

    return $provider;
  }

  /**
   * Get all providers.
   *
   * @return array<string, Provider>
   */
  public function all(): array {
    return $this->registry->all();
  }

  /**
   * Get enabled providers.
   *
   * @return array<string, Provider>
   */
  public function enabled(): array {
    return $this->registry->enabled();
  }

  /**
   * Boot all enabled providers.
   */
  public function boot(): void {
    $this->registry->boot();
  }
}
