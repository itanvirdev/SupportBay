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
use SupportBay\Modules\Tickets\Events\TicketMerged;
use SupportBay\Modules\Activities\Listeners\LogTicketMergedActivity;
use SupportBay\Modules\Tickets\Services\TicketMergeService;
use SupportBay\Modules\Tickets\Events\TicketResolved;
use SupportBay\Modules\Activities\Listeners\LogTicketResolvedActivity;
use SupportBay\Modules\Tickets\Services\TicketMetricService;
use SupportBay\Modules\Tickets\Http\Controllers\TicketMetricController;
use SupportBay\Common\Utilities\CsvExporter;
use SupportBay\Modules\Tickets\Repositories\TicketSlaBreachRepository;
use SupportBay\Modules\Tickets\Services\TicketTrackIdService;
use SupportBay\Modules\Tickets\Services\TicketLifecycleWorker;
use SupportBay\Modules\Tickets\Services\TicketAccessPolicy;

final class TicketServiceProvider extends ServiceProvider {

  protected array $listeners = [
    TicketChanged::class => [LogTicketChangedActivity::class],
    TicketMerged::class => [LogTicketMergedActivity::class],
    TicketResolved::class => [LogTicketResolvedActivity::class],
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
    // Retained only so permanent ticket deletion can clean legacy/future SLA rows.
    $container->singleton(TicketSlaBreachRepository::class);
    $container->singleton(CsvExporter::class);

    $container->singleton(TicketService::class);
    $container->singleton(TicketMergeService::class);
    $container->singleton(TicketMetricService::class);
    $container->singleton(TicketTrackIdService::class);
    $container->singleton(TicketLifecycleWorker::class);
    $container->singleton(TicketAccessPolicy::class);

    $container->singleton(TicketController::class);
    $container->singleton(TicketMetricController::class);
    $container->singleton(LogTicketChangedActivity::class);
    $container->singleton(LogTicketMergedActivity::class);
    $container->singleton(LogTicketResolvedActivity::class);
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
    $container->get(TicketLifecycleWorker::class)->register();
    add_action(
      'rest_api_init',
      [$container->get(TicketMetricController::class), 'registerRoutes'],
    );
  }
}
