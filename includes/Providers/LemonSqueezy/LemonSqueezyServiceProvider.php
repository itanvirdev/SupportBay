<?php

declare(strict_types=1);

namespace SupportBay\Providers\LemonSqueezy;

use SupportBay\Core\Container\Container;
use SupportBay\Core\Foundation\ServiceProvider;
use SupportBay\Core\Integrations\IntegrationManager;
use SupportBay\Providers\LemonSqueezy\Api\LemonSqueezyApiClient;
use SupportBay\Providers\LemonSqueezy\Services\LemonSqueezyProvisioningService;

final class LemonSqueezyServiceProvider extends ServiceProvider {
  public function register(Container $container): void { $container->singleton(LemonSqueezyApiClient::class); $container->singleton(LemonSqueezyProvider::class); $container->singleton(LemonSqueezyProvisioningService::class); }
  public function boot(Container $container): void { $container->get(IntegrationManager::class)->register($container->get(LemonSqueezyProvider::class)); add_action('init', [$container->get(LemonSqueezyProvisioningService::class), 'provision'], 1); }
}
