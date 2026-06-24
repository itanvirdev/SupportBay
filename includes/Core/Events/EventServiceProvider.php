<?php

declare(strict_types=1);

namespace SupportBay\Core\Events;

use SupportBay\Core\Container\Container;
use SupportBay\Core\Providers\ServiceProvider;
use SupportBay\Core\Events\EventDispatcher;

final class EventServiceProvider extends ServiceProvider {
  /**
   * Register event services.
   */
  public function register(Container $container): void {
    $container->singleton(
      EventDispatcher::class,
      fn() => new EventDispatcher()
    );
  }

  /**
   * Boot event services.
   */
  public function boot(Container $container): void {
    // Reserved for future:
    //
    // - Auto-discover listeners
    // - Event subscribers
    // - Queue integration
    // - Debug listeners
  }
}
