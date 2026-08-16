<?php
declare(strict_types=1);
namespace SupportBay\Modules\Categories;
use SupportBay\Core\Container\Container;use SupportBay\Core\Foundation\ServiceProvider;use SupportBay\Modules\Categories\Http\Controllers\CategoryController;use SupportBay\Modules\Categories\Repositories\CategoryRepository;use SupportBay\Modules\Categories\Services\CategoryService;
final class CategoryServiceProvider extends ServiceProvider {public function register(Container $container):void{$container->singleton(CategoryRepository::class);$container->singleton(CategoryService::class);$container->singleton(CategoryController::class);}public function boot(Container $container):void{parent::boot($container);add_action('rest_api_init',[$container->get(CategoryController::class),'registerRoutes']);}}
