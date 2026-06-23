<?php

declare(strict_types=1);

namespace SupportBay\Modules\Departments;

use SupportBay\Core\Container\Container;
use SupportBay\Core\Providers\ServiceProvider;
use SupportBay\Modules\Departments\Repositories\DepartmentRepository;
use SupportBay\Modules\Departments\Services\DepartmentService;

final class DepartmentServiceProvider extends ServiceProvider {

  /**
   * Register module services
   */
  public function register(Container $container): void {

    /**
     * Department Repository
     */
    $container->singleton(
      DepartmentRepository::class,
      fn() => new DepartmentRepository()
    );

    /**
     * Department Service
     */
    $container->singleton(
      DepartmentService::class,
      fn(Container $container) => new DepartmentService(
        $container->make(DepartmentRepository::class)
      )
    );
  }

  /**
   * Boot module
   */
  public function boot(Container $container): void {
    // Reserved for future:
    // - Hooks
    // - Events
    // - REST routes
    // - Cron jobs
  }
}
