<?php

declare(strict_types=1);

namespace SupportBay\Modules\Notifications;

use SupportBay\Core\Container\Container;
use SupportBay\Core\Foundation\ServiceProvider;
use SupportBay\Modules\Messages\Events\MessageCreated;
use SupportBay\Modules\Notifications\Channels\WordPressEmailChannel;
use SupportBay\Modules\Notifications\Contracts\NotificationChannel;
use SupportBay\Modules\Notifications\Listeners\SendMessageCreatedEmail;
use SupportBay\Modules\Notifications\Listeners\SendTicketCreatedEmails;
use SupportBay\Modules\Notifications\Services\NotificationService;
use SupportBay\Modules\Tickets\Events\TicketCreated;

final class NotificationServiceProvider extends ServiceProvider {
  /** @var array<class-string, array<class-string>> */
  protected array $listeners = [
    TicketCreated::class => [
      SendTicketCreatedEmails::class,
    ],
    MessageCreated::class => [
      SendMessageCreatedEmail::class,
    ],
  ];

  public function register(Container $container): void {
    $container->singleton(
      NotificationChannel::class,
      WordPressEmailChannel::class,
    );
    $container->singleton(NotificationService::class);
    $container->singleton(SendTicketCreatedEmails::class);
    $container->singleton(SendMessageCreatedEmail::class);
  }
}
