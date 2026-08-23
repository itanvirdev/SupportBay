<?php

declare(strict_types=1);

namespace SupportBay\Modules\AssignRules;

use SupportBay\Core\Container\Container;
use SupportBay\Core\Foundation\ServiceProvider;
use SupportBay\Modules\AssignRules\Http\Controllers\AssignRuleController;
use SupportBay\Modules\AssignRules\Listeners\ApplyAssignRules;
use SupportBay\Modules\AssignRules\Repositories\AssignRuleRepository;
use SupportBay\Modules\AssignRules\Services\AssignRuleService;
use SupportBay\Modules\Tickets\Events\TicketCreated;

final class AssignRuleServiceProvider extends ServiceProvider {
  protected array $listeners = [TicketCreated::class => [ApplyAssignRules::class]];
  public function register(Container $container): void {
    $container->singleton(AssignRuleRepository::class);
    $container->singleton(AssignRuleService::class);
    $container->singleton(AssignRuleController::class);
    $container->singleton(ApplyAssignRules::class);
  }
  public function boot(Container $container): void {
    parent::boot($container);
    add_action('init', [$container->get(AssignRuleService::class), 'provisionDefaults'], 3);
    add_action('rest_api_init', [$container->get(AssignRuleController::class), 'registerRoutes']);
  }
}
