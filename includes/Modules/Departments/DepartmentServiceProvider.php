<?php

declare(strict_types=1);

namespace SupportBay\Modules\Departments;

use SupportBay\Core\Container\Container;
use SupportBay\Core\Foundation\ServiceProvider;
use SupportBay\Modules\Departments\Repositories\DepartmentRepository;
use SupportBay\Modules\Departments\Services\DepartmentService;
use SupportBay\Modules\Departments\Http\Controllers\DepartmentController;

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

    $container->singleton(DepartmentController::class);
  }

  /**
   * Boot module
   */
  public function boot(Container $container): void {
    parent::boot($container);

    add_action('rest_api_init', [
      $container->get(DepartmentController::class),
      'registerRoutes',
    ]);
  }
}
