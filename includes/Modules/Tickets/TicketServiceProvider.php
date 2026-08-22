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
use SupportBay\Modules\Tickets\Events\TicketSplit;
use SupportBay\Modules\Tickets\Events\TicketResolved;
use SupportBay\Modules\Activities\Listeners\LogTicketResolvedActivity;
use SupportBay\Modules\Activities\Listeners\LogTicketSplitActivity;
use SupportBay\Modules\Tickets\Services\TicketSplitService;
use SupportBay\Modules\Tickets\Services\TicketMetricService;
use SupportBay\Modules\Tickets\Http\Controllers\TicketMetricController;
use SupportBay\Common\Utilities\CsvExporter;
use SupportBay\Modules\Tickets\Repositories\TicketSlaPolicyRepository;
use SupportBay\Modules\Tickets\Services\TicketSlaPolicyService;
use SupportBay\Modules\Tickets\Http\Controllers\TicketSlaPolicyController;
use SupportBay\Modules\Tickets\Events\TicketSlaBreached;
use SupportBay\Modules\Activities\Listeners\LogTicketSlaBreachedActivity;
use SupportBay\Modules\Tickets\Repositories\TicketSlaBreachRepository;
use SupportBay\Modules\Tickets\Services\TicketSlaBreachService;
use SupportBay\Modules\Tickets\Services\TicketSlaBreachWorker;
use SupportBay\Modules\Tickets\Services\TicketTrackIdService;
use SupportBay\Modules\Tickets\Services\TicketLifecycleWorker;

final class TicketServiceProvider extends ServiceProvider {

  protected array $listeners = [
    TicketChanged::class => [LogTicketChangedActivity::class],
    TicketMerged::class => [LogTicketMergedActivity::class],
    TicketSplit::class => [LogTicketSplitActivity::class],
    TicketResolved::class => [LogTicketResolvedActivity::class],
    TicketSlaBreached::class => [LogTicketSlaBreachedActivity::class],
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
    $container->singleton(TicketSlaPolicyRepository::class);
    $container->singleton(TicketSlaBreachRepository::class);
    $container->singleton(CsvExporter::class);

    $container->singleton(TicketService::class);
    $container->singleton(TicketMergeService::class);
    $container->singleton(TicketSplitService::class);
    $container->singleton(TicketMetricService::class);
    $container->singleton(TicketSlaPolicyService::class);
    $container->singleton(TicketSlaBreachService::class);
    $container->singleton(TicketSlaBreachWorker::class);
    $container->singleton(TicketTrackIdService::class);
    $container->singleton(TicketLifecycleWorker::class);

    $container->singleton(TicketController::class);
    $container->singleton(TicketMetricController::class);
    $container->singleton(TicketSlaPolicyController::class);
    $container->singleton(LogTicketChangedActivity::class);
    $container->singleton(LogTicketMergedActivity::class);
    $container->singleton(LogTicketSplitActivity::class);
    $container->singleton(LogTicketResolvedActivity::class);
    $container->singleton(LogTicketSlaBreachedActivity::class);
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
    $container->get(TicketSlaBreachWorker::class)->register();
    $container->get(TicketLifecycleWorker::class)->register();
    add_action(
      'rest_api_init',
      [$container->get(TicketMetricController::class), 'registerRoutes'],
    );
    add_action(
      'rest_api_init',
      [$container->get(TicketSlaPolicyController::class), 'registerRoutes'],
    );
  }
}
