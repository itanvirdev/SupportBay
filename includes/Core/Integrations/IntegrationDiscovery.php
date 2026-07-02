<?php

declare(strict_types=1);

namespace SupportBay\Core\Integrations;

use SupportBay\Core\Integrations\Contracts\IntegrationProvider;

final class IntegrationDiscovery {
  /**
   * Constructor.
   */
  public function __construct(
    private readonly IntegrationManager $manager,
  ) {
  }

  /**
   * Discover available integrations.
   */
  public function discover(): void {
    foreach ($this->integrations() as $integration) {
      $this->manager->register($integration);
    }
  }

  /**
   * Available integrations.
   *
   * Future versions may discover integrations from:
   *
   * - WordPress filters
   * - Composer packages
   * - Third-party plugins
   * - Automatic discovery
   * - Integration marketplace
   *
   * @return IntegrationProvider[]
   */
  private function integrations(): array {
    $integrations = [

      // Marketplace
      // new \SupportBay\Providers\Envato\EnvatoProvider(),

      // new \SupportBay\Providers\EDD\EDDProvider(),
      // new \SupportBay\Providers\WooCommerce\WooCommerceProvider(),
      // new \SupportBay\Providers\Freemius\FreemiusProvider(),
      // new \SupportBay\Providers\Paddle\PaddleProvider(),

      // AI
      // new \SupportBay\Providers\OpenAI\OpenAIProvider(),
      // new \SupportBay\Providers\Gemini\GeminiProvider(),

      // Notifications
      // new \SupportBay\Providers\SMTP\SMTPProvider(),
      // new \SupportBay\Providers\Slack\SlackProvider(),
    ];

    /**
     * Filter discovered integrations.
     *
     * Allows third-party plugins to register their own
     * SupportBay integrations.
     *
     * @param IntegrationProvider[] $integrations
     */
    return apply_filters(
      'supportbay/integrations',
      $integrations
    );
  }
}
