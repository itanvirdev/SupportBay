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
    $container->singleton(
      MessageRepository::class,
      fn() => new MessageRepository()
    );

    $container->singleton(
      MessageService::class,
      fn(Container $c) => new MessageService(
        $c->make(MessageRepository::class)
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
