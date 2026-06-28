<?php

declare(strict_types=1);

namespace SupportBay\Core\Providers;

use SupportBay\Core\Container\Container;
use SupportBay\Modules\Tickets\TicketServiceProvider;
use SupportBay\Modules\Messages\MessageServiceProvider;
use SupportBay\Modules\Departments\DepartmentServiceProvider;
use SupportBay\Core\Events\EventServiceProvider;
use SupportBay\Modules\Activities\ActivityServiceProvider;
use SupportBay\Modules\Attachments\AttachmentServiceProvider;

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
    self::addProvider(new EventServiceProvider());
  }

  /**
   * Module providers
   */
  private static function registerModuleProviders(Container $container): void {
    self::addProvider(new TicketServiceProvider());
    self::addProvider(new DepartmentServiceProvider());
    self::addProvider(new ActivityServiceProvider());
    self::addProvider(new MessageServiceProvider());
    self::addProvider(new AttachmentServiceProvider());
  }

  /**
   * Boot all providers
   */
  public static function bootProviders(Container $container): void {
    foreach (self::$providers as $provider) {

      if (method_exists($provider, 'register')) {
        $provider->register($container);
      }

      if (method_exists($provider, 'boot')) {
        $provider->boot($container);
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
