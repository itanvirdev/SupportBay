<?php

declare(strict_types=1);

namespace SupportBay\Modules\Departments;

use SupportBay\Core\Container\Container;
use SupportBay\Core\Foundation\ServiceProvider;
use SupportBay\Modules\Departments\Repositories\DepartmentRepository;
use SupportBay\Modules\Departments\Services\DepartmentService;

final class DepartmentServiceProvider extends ServiceProvider {

  // protected array $listeners = [

  //   DepartmentCreated::class => [

  //     DepartmentActivityListener::class,

  //   ],

  // ];

  /**
   * Register module services
   */
  /**
   * Register services.
   */
  public function register(Container $container): void {
    $container->singleton(DepartmentRepository::class);

    $container->singleton(DepartmentService::class);
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
