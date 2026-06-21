<?php

declare(strict_types=1);

namespace SupportBay\Modules\Messages;

use SupportBay\Core\Container\Container;
use SupportBay\Core\Providers\ServiceProvider;
use SupportBay\Modules\Messages\Repositories\MessageRepository;
use SupportBay\Modules\Messages\Services\MessageService;

final class MessageServiceProvider extends ServiceProvider {
  /**
   * Register module services into container
   */
  public function register(Container $container): void {
    /**
     * Message Repository
     */
    $container->singleton(
      MessageRepository::class,
      fn() => new MessageRepository()
    );

    /**
     * Message Service
     */
    $container->singleton(
      MessageService::class,
      fn($container) => new MessageService(
        $container->make(MessageRepository::class)
      )
    );
  }

  /**
   * Boot logic (reserved for future)
   */
  public function boot(Container $container): void {
    // Future:
    // - event listeners
    // - hooks (do_action)
    // - websocket bindings
  }
}
