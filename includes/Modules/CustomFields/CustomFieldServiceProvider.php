<?php

declare(strict_types=1);

namespace SupportBay\Modules\CustomFields;

use SupportBay\Core\Container\Container;
use SupportBay\Core\Foundation\ServiceProvider;
use SupportBay\Modules\CustomFields\Http\Controllers\CustomFieldController;
use SupportBay\Modules\CustomFields\Repositories\CustomFieldRepository;
use SupportBay\Modules\CustomFields\Services\CustomFieldService;

final class CustomFieldServiceProvider extends ServiceProvider {
  public function register(Container $container): void {
    $container->singleton(CustomFieldRepository::class);
    $container->singleton(CustomFieldService::class);
    $container->singleton(CustomFieldController::class);
  }

  public function boot(Container $container): void {
    parent::boot($container);
    add_action('rest_api_init', [$container->get(CustomFieldController::class), 'registerRoutes']);
  }
}
