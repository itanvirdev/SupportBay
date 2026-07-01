<?php

declare(strict_types=1);

namespace SupportBay\Modules\Activities;

use SupportBay\Core\Container\Container;
use SupportBay\Core\Foundation\ServiceProvider;
use SupportBay\Modules\Activities\Repositories\ActivityRepository;
use SupportBay\Modules\Activities\Services\ActivityService;

final class ActivityServiceProvider extends ServiceProvider {
  /**
   * Register services.
   */
  public function register(Container $container): void {
    $container->singleton(ActivityRepository::class);

    $container->singleton(ActivityService::class);
  }
}
