<?php

declare(strict_types=1);

namespace SupportBay\Modules\Providers;

use SupportBay\Core\Container\Container;
use SupportBay\Core\Foundation\ServiceProvider;
use SupportBay\Modules\Providers\Repositories\ProviderRepository;
use SupportBay\Modules\Providers\Services\ProviderService;
use SupportBay\Modules\Providers\Http\Controllers\ProviderController;

final class ProviderServiceProvider extends ServiceProvider {
  /**
   * Event listeners.
   *
   * @var array<class-string, array<class-string>>
   */
  protected array $listeners = [
    // Future:
    //
    // ProviderEnabled::class => [
    //     LogProviderEnabledActivity::class,
    // ],
    //
    // ProviderDisabled::class => [
    //     LogProviderDisabledActivity::class,
    // ],
    //
    // ProviderConnected::class => [
    //     LogProviderConnectedActivity::class,
    // ],
    //
    // ProviderConnectionFailed::class => [
    //     LogProviderConnectionFailedActivity::class,
    // ],
  ];

  /**
   * Register services.
   */
  public function register(Container $container): void {
    $container->singleton(ProviderRepository::class);

    $container->singleton(ProviderService::class);

    $container->singleton(ProviderController::class);
  }

  public function boot(Container $container): void {
    parent::boot($container);

    add_action('rest_api_init', [
      $container->get(ProviderController::class),
      'registerRoutes',
    ]);
  }
}
