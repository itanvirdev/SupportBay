<?php

declare(strict_types=1);

namespace SupportBay\Modules\Tickets;

use SupportBay\Core\Container\Container;
use SupportBay\Core\Foundation\ServiceProvider;
use SupportBay\Modules\Tickets\Services\TicketService;
use SupportBay\Modules\Tickets\Repositories\TicketRepository;
use SupportBay\Modules\Tickets\Http\Controllers\TicketController;
use SupportBay\Modules\Tickets\Events\TicketChanged;
use SupportBay\Modules\Activities\Listeners\LogTicketChangedActivity;

final class TicketServiceProvider extends ServiceProvider {

  protected array $listeners = [
    TicketChanged::class => [LogTicketChangedActivity::class],
  ];

  // protected array $listeners = [

  //   TicketCreated::class => [

  //     AutoAssignListener::class,

  //     TicketCreatedNotificationListener::class,

  //   ],

  // ];

  /**
   * Register module services into container
   */
  public function register(Container $container): void {
    $container->singleton(TicketRepository::class);

    $container->singleton(TicketService::class);

    $container->singleton(TicketController::class);
    $container->singleton(LogTicketChangedActivity::class);
  }

  /**
   * Boot logic (reserved for future)
   */
  public function boot(Container $container): void {
    parent::boot($container);

    add_action(
      'rest_api_init',
      [$container->get(TicketController::class), 'registerRoutes'],
    );
  }
}
