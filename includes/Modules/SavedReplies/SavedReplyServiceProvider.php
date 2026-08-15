<?php

declare(strict_types=1);

namespace SupportBay\Modules\SavedReplies;

use SupportBay\Core\Container\Container;
use SupportBay\Core\Foundation\ServiceProvider;
use SupportBay\Modules\SavedReplies\Http\Controllers\SavedReplyController;
use SupportBay\Modules\SavedReplies\Repositories\SavedReplyRepository;
use SupportBay\Modules\SavedReplies\Services\SavedReplyService;

final class SavedReplyServiceProvider extends ServiceProvider {
  public function register(Container $container): void {
    $container->singleton(SavedReplyRepository::class);
    $container->singleton(SavedReplyService::class);
    $container->singleton(SavedReplyController::class);
  }

  public function boot(Container $container): void {
    parent::boot($container);
    add_action('rest_api_init', [$container->get(SavedReplyController::class), 'registerRoutes']);
  }
}
