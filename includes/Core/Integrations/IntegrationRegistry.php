<?php

declare(strict_types=1);

namespace SupportBay\Core\Integrations;

use RuntimeException;
use SupportBay\Core\Integrations\Contracts\IntegrationProvider;

final class IntegrationRegistry {
  /**
   * Registered integrations.
   *
   * @var array<string, IntegrationProvider>
   */
  private array $integrations = [];

  /**
   * Register an integration.
   *
   * @throws RuntimeException
   */
  public function register(IntegrationProvider $integration): void {
    $slug = $integration->slug();

    if ($this->has($slug)) {
      throw new RuntimeException(
        sprintf(
          'Integration "%s" is already registered.',
          $slug
        )
      );
    }

    $this->integrations[$slug] = $integration;
  }

  /**
   * Determine whether an integration exists.
   */
  public function has(string $slug): bool {
    return isset($this->integrations[$slug]);
  }

  /**
   * Retrieve an integration.
   */
  public function get(string $slug): ?IntegrationProvider {
    return $this->integrations[$slug] ?? null;
  }

  /**
   * Retrieve all registered integrations.
   *
   * @return array<string, IntegrationProvider>
   */
  public function all(): array {
    return $this->integrations;
  }

  /**
   * Boot all registered integrations.
   */
  public function boot(): void {
    foreach ($this->integrations as $integration) {
      $integration->boot();
    }
  }

  /**
   * Unregister an integration.
   */
  public function unregister(string $slug): void {
    unset($this->integrations[$slug]);
  }

  /**
   * Remove all integrations.
   *
   * Mainly used during testing.
   */
  public function clear(): void {
    $this->integrations = [];
  }
}
