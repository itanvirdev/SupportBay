<?php

declare(strict_types=1);

namespace SupportBay\Modules\Tickets;

use SupportBay\Core\Container\Container;
use SupportBay\Core\Foundation\ServiceProvider;
use SupportBay\Modules\Tickets\Services\TicketService;
use SupportBay\Modules\Tickets\Repositories\TicketRepository;

final class TicketServiceProvider extends ServiceProvider {

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
  }

  /**
   * Boot logic (reserved for future)
   */
  public function boot(Container $container): void {
    // Future: hooks, cron, REST routes
  }
}
