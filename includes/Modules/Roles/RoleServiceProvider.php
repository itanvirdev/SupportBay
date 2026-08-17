<?php

declare(strict_types=1);

namespace SupportBay\Modules\Roles;

use SupportBay\Core\Container\Container;
use SupportBay\Core\Foundation\ServiceProvider;
use SupportBay\Modules\Roles\Http\Controllers\SupportRoleController;
use SupportBay\Modules\Roles\Repositories\SupportRoleRepository;
use SupportBay\Modules\Roles\Services\SupportRoleService;

final class RoleServiceProvider extends ServiceProvider {
  public function register(Container $container): void {
    $container->singleton(SupportRoleRepository::class);
    $container->singleton(SupportRoleService::class);
    $container->singleton(SupportRoleController::class);
  }

  public function boot(Container $container): void {
    parent::boot($container);
    $service = $container->get(SupportRoleService::class);
    add_action('rest_api_init', [$container->get(SupportRoleController::class), 'registerRoutes']);
    add_filter('editable_roles', [$service, 'filterEditableRoles']);
  }
}
