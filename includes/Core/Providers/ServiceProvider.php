<?php

declare(strict_types=1);

namespace SupportBay\Core\Providers;

use SupportBay\Core\Container\Container;

abstract class ServiceProvider {
  /**
   * Register services into container
   */
  public function register(Container $container): void {
    // override in child
  }

  /**
   * Boot logic after all providers registered
   */
  public function boot(Container $container): void {
    // override in child
  }
}
