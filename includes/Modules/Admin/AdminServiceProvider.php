<?php

declare(strict_types=1);

namespace SupportBay\Modules\Admin;

use SupportBay\Core\Container\Container;
use SupportBay\Core\Foundation\ServiceProvider;
use SupportBay\Modules\Admin\Http\AdminTicketController;

final class AdminServiceProvider extends ServiceProvider {
  public function register(Container $container): void {
    $container->singleton(AdminPage::class);
    $container->singleton(AdminTicketController::class);
  }

  public function boot(Container $container): void {
    parent::boot($container);
    $container->get(AdminPage::class)->register();
    $controller = $container->get(AdminTicketController::class);
    add_action('rest_api_init', [$controller, 'registerRoutes']);
    add_filter('rest_pre_serve_request', [$controller, 'serveDownload'], 10, 4);
  }
}
