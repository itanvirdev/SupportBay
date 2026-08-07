<?php

declare(strict_types=1);

namespace SupportBay\Modules\Customers;

use SupportBay\Core\Container\Container;
use SupportBay\Core\Foundation\ServiceProvider;
use SupportBay\Modules\Customers\Repositories\CustomerRepository;
use SupportBay\Modules\Customers\Repositories\WordPressUserRepository;
use SupportBay\Modules\Customers\Services\CustomerService;
use SupportBay\Modules\Customers\Http\Controllers\CustomerController;

final class CustomerServiceProvider extends ServiceProvider {
  /**
   * Register services.
   */
  public function register(Container $container): void {
    $container->singleton(CustomerRepository::class);

    $container->singleton(WordPressUserRepository::class);

    $container->singleton(CustomerService::class);

    $container->singleton(CustomerController::class);
  }

  /**
   * Boot services.
   */
  public function boot(Container $container): void {
    parent::boot($container);

    add_action('rest_api_init', [
      $container->get(CustomerController::class),
      'registerRoutes',
    ]);
  }
}
