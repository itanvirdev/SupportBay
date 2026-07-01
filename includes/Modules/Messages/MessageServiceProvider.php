<?php

declare(strict_types=1);

namespace SupportBay\Modules\Messages;

use SupportBay\Core\Container\Container;
use SupportBay\Core\Events\EventDispatcher;
use SupportBay\Core\Foundation\ServiceProvider;
use SupportBay\Modules\Messages\Events\MessageCreated;
use SupportBay\Modules\Messages\Listeners\SyncTicketReplyListener;
use SupportBay\Modules\Messages\Repositories\MessageRepository;
use SupportBay\Modules\Messages\Services\MessageService;
use SupportBay\Modules\Tickets\Services\TicketService;
use SupportBay\Modules\Activities\Listeners\LogMessageCreatedActivity;

final class MessageServiceProvider extends ServiceProvider {
  /**
   * Event listeners.
   *
   * @var array<class-string, array<class-string>>
   */
  protected array $listeners = [
    MessageCreated::class => [
      SyncTicketReplyListener::class,
      LogMessageCreatedActivity::class,
    ],
  ];

  /**
   * Register module services.
   */
  /**
   * Register services.
   */
  public function register(Container $container): void {
    $container->singleton(MessageRepository::class);

    $container->singleton(MessageService::class);

    $container->singleton(SyncTicketReplyListener::class);

    $container->singleton(LogMessageCreatedActivity::class);
  }
}
