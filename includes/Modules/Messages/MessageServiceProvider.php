<?php

declare(strict_types=1);

namespace SupportBay\Modules\Messages;

use SupportBay\Core\Container\Container;
use SupportBay\Core\Events\EventDispatcher;
use SupportBay\Core\Providers\ServiceProvider;
use SupportBay\Modules\Messages\Repositories\MessageRepository;
use SupportBay\Modules\Messages\Services\MessageService;
use SupportBay\Modules\Messages\Events\MessageCreated;
use SupportBay\Modules\Messages\Listeners\SyncTicketReplyListener;
use SupportBay\Modules\Tickets\Repositories\TicketRepository;

final class MessageServiceProvider extends ServiceProvider {
  /**
   * Register services.
   */
  public function register(Container $container): void {
    $container->singleton(
      MessageRepository::class,
      fn() => new MessageRepository()
    );

    $container->singleton(
      MessageService::class,
      fn(Container $c) => new MessageService(
        $c->get(MessageRepository::class),
        $c->get(EventDispatcher::class)
      )
    );
  }

  /**
   * Register listeners.
   */
  public function boot(Container $container): void {
    /** @var EventDispatcher $dispatcher */
    $dispatcher = $container->get(EventDispatcher::class);

    $dispatcher->listen(
      MessageCreated::class,
      new SyncTicketReplyListener(
        $container->get(TicketRepository::class)
      )
    );
  }
}
