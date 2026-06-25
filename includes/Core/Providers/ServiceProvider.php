<?php

declare(strict_types=1);

namespace SupportBay\Core\Providers;

use SupportBay\Core\Container\Container;
use SupportBay\Core\Events\ListenerRegistry;

abstract class ServiceProvider {
  /**
   * Event listeners.
   *
   * @var array<class-string, array<class-string>>
   */
  protected array $listeners = [];

  /**
   * Register services.
   */
  abstract public function register(Container $container): void;

  /**
   * Boot provider.
   */
  public function boot(Container $container): void {
    $this->registerListeners();
  }

  /**
   * Register all event listeners.
   */
  protected function registerListeners(): void {
    foreach ($this->listeners as $event => $listeners) {

      foreach ($listeners as $listener) {
        ListenerRegistry::add(
          $event,
          $listener
        );
      }
    }
  }
}
