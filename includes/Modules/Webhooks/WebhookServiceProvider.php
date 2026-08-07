<?php

declare(strict_types=1);

namespace SupportBay\Modules\Webhooks;

use SupportBay\Core\Container\Container;
use SupportBay\Core\Foundation\ServiceProvider;
use SupportBay\Modules\Messages\Events\MessageCreated;
use SupportBay\Modules\Tickets\Events\TicketClosed;
use SupportBay\Modules\Tickets\Events\TicketCreated;
use SupportBay\Modules\Tickets\Events\TicketReopened;
use SupportBay\Modules\Webhooks\Contracts\WebhookDispatcher;
use SupportBay\Modules\Webhooks\Dispatchers\WordPressHookDispatcher;
use SupportBay\Modules\Webhooks\Listeners\DispatchDomainWebhook;

final class WebhookServiceProvider extends ServiceProvider {
  /** @var array<class-string, array<class-string>> */
  protected array $listeners = [
    TicketCreated::class => [DispatchDomainWebhook::class],
    TicketClosed::class => [DispatchDomainWebhook::class],
    TicketReopened::class => [DispatchDomainWebhook::class],
    MessageCreated::class => [DispatchDomainWebhook::class],
  ];

  public function register(Container $container): void {
    $container->singleton(
      WebhookDispatcher::class,
      WordPressHookDispatcher::class,
    );
    $container->singleton(DispatchDomainWebhook::class);
  }
}
