<?php

declare(strict_types=1);

namespace SupportBay\Modules\Customers;

use SupportBay\Core\Container\Container;
use SupportBay\Core\Foundation\ServiceProvider;
use SupportBay\Modules\Customers\Repositories\CustomerRepository;
use SupportBay\Modules\Customers\Services\CustomerService;

final class CustomerServiceProvider extends ServiceProvider {
  /**
   * Register services.
   */
  public function register(Container $container): void {
    $container->singleton(CustomerRepository::class);

    $container->singleton(CustomerService::class);
  }

  /**
   * Boot services.
   */
  public function boot(Container $container): void {
    // Reserved for future:
    // - Customer events
    // - REST routes
    // - Rewrite rules
    // - WP hooks
    // - Scheduled tasks
  }
}
