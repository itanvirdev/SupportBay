<?php

declare(strict_types=1);

namespace SupportBay\Core;

use SupportBay\Core\Container\Container;
use SupportBay\Core\Foundation\ServiceProviderRegistry;
use SupportBay\Core\Integrations\IntegrationDiscovery;
use SupportBay\Core\Integrations\IntegrationManager;
use SupportBay\Core\Integrations\IntegrationRegistry;
use SupportBay\Core\Authorization\CapabilityManager;

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
   * Static reference for dev/testing access
   */
  private static ?Container $staticContainer = null;

  /**
   * Initialize Application
   */
  public function __construct() {
    $this->container = new Container();

    // allow global access
    self::$staticContainer = $this->container;
  }

  /**
   * Boot the SupportBay application
   */
  public function boot(): void {
    if ($this->booted) {
      return;
    }

    $this->registerCoreBindings();

    add_action(
      'init',
      [CapabilityManager::class, 'register'],
      1,
    );

    ServiceProviderRegistry::register($this->container);

    $this->booted = true;
  }

  /**
   * Register core container bindings
   */
  private function registerCoreBindings(): void {
    $this->container->instance(Application::class, $this);
    $this->container->instance(Container::class, $this->container);
    $this->container->singleton(IntegrationRegistry::class);
    $this->container->singleton(IntegrationManager::class);
    $this->container->singleton(IntegrationDiscovery::class);
  }

  /**
   * Get container instance
   */
  // public function container(): Container {
  //   return $this->container;
  // }

  /**
   * Static container access (for dev tools / flow test)
   */
  public static function container(): Container {
    if (!self::$staticContainer) {
      throw new \RuntimeException('Container not initialized yet.');
    }

    return self::$staticContainer;
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
