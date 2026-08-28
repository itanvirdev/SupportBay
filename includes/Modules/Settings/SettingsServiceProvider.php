<?php

declare(strict_types=1);

namespace SupportBay\Modules\Settings;

use SupportBay\Core\Container\Container;
use SupportBay\Core\Foundation\ServiceProvider;
use SupportBay\Modules\Settings\Http\Controllers\GeneralSettingsController;
use SupportBay\Modules\Settings\Repositories\GeneralSettingsRepository;
use SupportBay\Modules\Settings\Services\GeneralSettingsService;
use SupportBay\Modules\Settings\Services\RecaptchaService;
use SupportBay\Modules\Settings\Repositories\WeekendHolidaySettingsRepository;
use SupportBay\Modules\Settings\Services\WeekendHolidaySettingsService;
use SupportBay\Modules\Settings\Http\Controllers\WeekendHolidaySettingsController;
use SupportBay\Modules\Settings\Repositories\AutoCloseSettingsRepository;
use SupportBay\Modules\Settings\Services\AutoCloseSettingsService;
use SupportBay\Modules\Settings\Http\Controllers\AutoCloseSettingsController;

final class SettingsServiceProvider extends ServiceProvider {
  public function register(Container $container): void {
    $container->singleton(GeneralSettingsRepository::class);
    $container->singleton(GeneralSettingsService::class);
    $container->singleton(RecaptchaService::class);
    $container->singleton(GeneralSettingsController::class);
    $container->singleton(WeekendHolidaySettingsRepository::class);
    $container->singleton(WeekendHolidaySettingsService::class);
    $container->singleton(WeekendHolidaySettingsController::class);
    $container->singleton(AutoCloseSettingsRepository::class);
    $container->singleton(AutoCloseSettingsService::class);
    $container->singleton(AutoCloseSettingsController::class);
  }
  public function boot(Container $container): void {
    add_action('rest_api_init',[$container->get(GeneralSettingsController::class),'registerRoutes']);
    add_action('rest_api_init',[$container->get(WeekendHolidaySettingsController::class),'registerRoutes']);
    add_action('rest_api_init',[$container->get(AutoCloseSettingsController::class),'registerRoutes']);
  }
}
