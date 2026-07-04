<?php

declare(strict_types=1);

namespace SupportBay\Providers\Envato;

use SupportBay\Core\Integrations\Contracts\IntegrationProvider;
use SupportBay\Modules\Providers\Enums\ProviderCategory;

final class EnvatoProvider implements IntegrationProvider {
  /**
   * Unique integration identifier.
   */
  public function slug(): string {
    return 'envato';
  }

  /**
   * Display name.
   */
  public function name(): string {
    return 'Envato';
  }

  /**
   * Integration category.
   */
  public function category(): ProviderCategory {
    return ProviderCategory::MARKETPLACE;
  }

  /**
   * Integration version.
   */
  public function version(): string {
    return '1.0.0';
  }

  /**
   * Boot the integration.
   */
  public function boot(): void {
    /**
     * Future responsibilities:
     *
     * - Register OAuth routes
     * - Register REST API endpoints
     * - Register AJAX actions
     * - Register webhooks
     * - Register scheduled sync jobs
     * - Register admin settings
     * - Register CLI commands
     */
  }
}
