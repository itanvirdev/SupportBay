<?php

declare(strict_types=1);

namespace SupportBay\Modules\Portal;

use SupportBay\Core\Container\Container;
use SupportBay\Core\Foundation\ServiceProvider;
use SupportBay\Modules\Portal\Http\Controllers\PortalController;
use SupportBay\Modules\Portal\Services\PortalService;

final class PortalServiceProvider extends ServiceProvider {
  public function register(Container $container): void {
    $container->singleton(PortalService::class);
    $container->singleton(PortalController::class);
  }

  public function boot(Container $container): void {
    $controller = $container->get(PortalController::class);

    add_action(
      'rest_api_init',
      [$controller, 'registerRoutes']
    );
  }
}
