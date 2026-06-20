<?php

declare(strict_types=1);

namespace SupportBay\Core;

use SupportBay\Core\Container\Container;
use SupportBay\Core\Providers\ProviderRegistry;

final class Application {
  /**
   * Dependency Injection Container
   */
  private Container $container;

  /**
   * Boot state
   */
  private bool $booted = false;

  /**
   * Initialize Application
   */
  public function __construct() {
    $this->container = new Container();
  }

  /**
   * Boot the SupportBay application
   */
  public function boot(): void {
    if ($this->booted) {
      return;
    }

    $this->registerCoreBindings();

    ProviderRegistry::register($this->container);

    $this->booted = true;
  }

  /**
   * Register core container bindings
   */
  private function registerCoreBindings(): void {
    $this->container->singleton('app', $this);
    $this->container->singleton('container', $this->container);
  }

  /**
   * Get container instance
   */
  public function container(): Container {
    return $this->container;
  }

  /**
   * Resolve a service
   */
  public function make(string $key) {
    return $this->container->get($key);
  }

  /**
   * Check boot status
   */
  public function isBooted(): bool {
    return $this->booted;
  }
}
