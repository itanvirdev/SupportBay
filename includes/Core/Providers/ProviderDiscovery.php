<?php

declare(strict_types=1);

namespace SupportBay\Core\Providers;

use SupportBay\Core\Providers\Contracts\IntegrationProvider;

final class ProviderDiscovery {
  /**
   * Provider manager.
   */
  public function __construct(
    private readonly ProviderManager $manager,
  ) {
  }

  /**
   * Discover available providers.
   */
  public function discover(): void {
    foreach ($this->providers() as $provider) {
      $this->manager->register($provider);
    }
  }

  /**
   * Available providers.
   *
   * Future versions may discover providers from:
   * - WordPress filters
   * - Composer packages
   * - Third-party plugins
   * - Automatic discovery
   *
   * @return IntegrationProvider[]
   */
  private function providers(): array {
    return [
      // new EnvatoProvider(),
      // new EddProvider(),
      // new WooCommerceProvider(),
    ];
  }
}
