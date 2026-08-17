<?php

declare(strict_types=1);

namespace SupportBay\Modules\Tags;

use SupportBay\Core\Container\Container;
use SupportBay\Core\Foundation\ServiceProvider;
use SupportBay\Modules\Tags\Http\Controllers\TagController;
use SupportBay\Modules\Tags\Repositories\TagRepository;
use SupportBay\Modules\Tags\Services\TagService;
use SupportBay\Modules\Tags\Events\TicketTagChanged;
use SupportBay\Modules\Activities\Listeners\LogTicketTagChangedActivity;

final class TagServiceProvider extends ServiceProvider {
  protected array $listeners = [
    TicketTagChanged::class => [LogTicketTagChangedActivity::class],
  ];

  public function register(Container $container): void {
    $container->singleton(TagRepository::class);
    $container->singleton(TagService::class);
    $container->singleton(TagController::class);
    $container->singleton(LogTicketTagChangedActivity::class);
  }

  public function boot(Container $container): void {
    parent::boot($container);
    add_action(
      'rest_api_init',
      [$container->get(TagController::class), 'registerRoutes'],
    );
  }
}
