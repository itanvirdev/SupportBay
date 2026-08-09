<?php

declare(strict_types=1);

namespace SupportBay\Modules\Admin;

use SupportBay\Core\Container\Container;
use SupportBay\Core\Foundation\ServiceProvider;

final class AdminServiceProvider extends ServiceProvider {
  public function register(Container $container): void {
    $container->singleton(AdminPage::class);
  }

  public function boot(Container $container): void {
    parent::boot($container);
    $container->get(AdminPage::class)->register();
  }
}
