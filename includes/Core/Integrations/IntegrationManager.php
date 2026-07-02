<?php

declare(strict_types=1);

namespace SupportBay\Core\Integrations;

use RuntimeException;
use SupportBay\Core\Integrations\Contracts\IntegrationProvider;

final class IntegrationManager {
  /**
   * Constructor.
   */
  public function __construct(
    private readonly IntegrationRegistry $registry,
  ) {
  }

  /**
   * Register an integration.
   */
  public function register(IntegrationProvider $integration): void {
    $this->registry->register($integration);
  }

  /**
   * Determine whether an integration exists.
   */
  public function has(string $slug): bool {
    return $this->registry->has($slug);
  }

  /**
   * Retrieve an integration.
   *
   * @throws RuntimeException
   */
  public function integration(string $slug): IntegrationProvider {
    $integration = $this->registry->get($slug);

    if (! $integration) {
      throw new RuntimeException(
        sprintf(
          'Integration "%s" is not registered.',
          $slug
        )
      );
    }

    return $integration;
  }

  /**
   * Retrieve all registered integrations.
   *
   * @return array<string, IntegrationProvider>
   */
  public function all(): array {
    return $this->registry->all();
  }

  /**
   * Determine whether any integrations are registered.
   */
  public function isEmpty(): bool {
    return empty($this->registry->all());
  }

  /**
   * Boot all registered integrations.
   */
  public function boot(): void {
    $this->registry->boot();
  }

  /**
   * Remove an integration.
   */
  public function unregister(string $slug): void {
    $this->registry->unregister($slug);
  }

  /**
   * Remove all integrations.
   *
   * Mainly used during testing.
   */
  public function clear(): void {
    $this->registry->clear();
  }
}
