<?php

declare(strict_types=1);

namespace SupportBay\Providers\Envato;

use SupportBay\Core\Container\Container;
use SupportBay\Core\Foundation\ServiceProvider;
use SupportBay\Core\Integrations\IntegrationManager;
use SupportBay\Providers\Envato\Api\EnvatoApiClient;
use SupportBay\Providers\Envato\Services\EnvatoCustomerService;
use SupportBay\Providers\Envato\Services\EnvatoOAuthService;
use SupportBay\Providers\Envato\Services\EnvatoPurchaseService;
use SupportBay\Providers\Envato\Routes\OAuthRoutes;

final class EnvatoServiceProvider extends ServiceProvider {
  /**
   * Register services.
   */
  public function register(Container $container): void {
    $container->singleton(EnvatoApiClient::class);

    $container->singleton(EnvatoOAuthService::class);

    $container->singleton(EnvatoPurchaseService::class);

    $container->singleton(EnvatoCustomerService::class);

    $container->singleton(EnvatoProvider::class);

    $container->singleton(OAuthRoutes::class);
  }

  /**
   * Boot services.
   */
  public function boot(Container $container): void {
    $container
      ->get(IntegrationManager::class)
      ->register(
        $container->get(EnvatoProvider::class)
      );

    $container->get(OAuthRoutes::class)->register();
  }
}
