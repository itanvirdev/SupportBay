<?php

declare(strict_types=1);

namespace SupportBay\Modules\Tickets;

use SupportBay\Core\Container\Container;
use SupportBay\Core\Providers\ServiceProvider;
use SupportBay\Modules\Tickets\Services\TicketService;
use SupportBay\Modules\Tickets\Repositories\TicketRepository;

final class TicketServiceProvider extends ServiceProvider {
  public function register(Container $container): void {
    $container->bind('ticket_repository', function () {
      return new TicketRepository();
    });

    $container->bind('ticket_service', function ($c) {
      return new TicketService(
        $c->get('ticket_repository')
      );
    });
  }

  public function boot(Container $container): void {
    // Future: hooks, cron, REST routes
  }
}
