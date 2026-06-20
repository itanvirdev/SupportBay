<?php

declare(strict_types=1);

namespace SupportBay\Core\Providers;

use SupportBay\Core\Container\Container;

final class ProviderRegistry {
  /**
   * Registered providers
   */
  private static array $providers = [];

  /**
   * Register system providers
   */
  public static function register(Container $container): void {
    self::registerCoreProviders($container);
    self::registerModuleProviders($container);
    self::bootProviders($container);
  }

  /**
   * Core providers
   */
  private static function registerCoreProviders(Container $container): void {
    // Example future:
    // self::$providers[] = new EventServiceProvider();
  }

  /**
   * Module providers
   */
  private static function registerModuleProviders(Container $container): void {
    // Later: auto-load Modules/*
  }

  /**
   * Boot all providers
   */
  private static function bootProviders(Container $container): void {
    foreach (self::$providers as $provider) {

      if (method_exists($provider, 'register')) {
        $provider->register($container);
      }

      if (method_exists($provider, 'boot')) {
        $provider->boot();
      }
    }
  }

  /**
   * Add provider
   */
  public static function addProvider(object $provider): void {
    self::$providers[] = $provider;
  }

  /**
   * Reset providers (testing)
   */
  public static function reset(): void {
    self::$providers = [];
  }
}
