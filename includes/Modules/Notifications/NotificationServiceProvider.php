<?php

declare(strict_types=1);

namespace SupportBay\Modules\Notifications;

use SupportBay\Core\Container\Container;
use SupportBay\Core\Foundation\ServiceProvider;
use SupportBay\Modules\Messages\Events\MessageCreated;
use SupportBay\Modules\Notifications\Channels\WordPressEmailChannel;
use SupportBay\Modules\Notifications\Contracts\NotificationChannel;
use SupportBay\Modules\Notifications\Http\Controllers\NotificationPreferenceController;
use SupportBay\Modules\Notifications\Http\Controllers\NotificationTemplateController;
use SupportBay\Modules\Notifications\Listeners\SendMessageCreatedEmail;
use SupportBay\Modules\Notifications\Listeners\SendTicketCreatedEmails;
use SupportBay\Modules\Notifications\Listeners\SendTicketLifecycleEmail;
use SupportBay\Modules\Notifications\Listeners\SendTicketAssignedEmail;
use SupportBay\Modules\Notifications\Repositories\NotificationLogRepository;
use SupportBay\Modules\Notifications\Repositories\NotificationPreferenceRepository;
use SupportBay\Modules\Notifications\Repositories\NotificationRetentionRepository;
use SupportBay\Modules\Notifications\Repositories\NotificationTemplateRepository;
use SupportBay\Modules\Notifications\Services\NotificationRetryWorker;
use SupportBay\Modules\Notifications\Services\NotificationCleanupWorker;
use SupportBay\Modules\Notifications\Services\NotificationPreferenceService;
use SupportBay\Modules\Notifications\Services\NotificationRetentionService;
use SupportBay\Modules\Notifications\Services\NotificationScheduler;
use SupportBay\Modules\Notifications\Services\NotificationService;
use SupportBay\Modules\Notifications\Services\NotificationTemplateService;
use SupportBay\Modules\Notifications\Templates\DefaultNotificationTemplates;
use SupportBay\Modules\Tickets\Events\TicketCreated;
use SupportBay\Modules\Tickets\Events\TicketAssignmentChanged;
use SupportBay\Modules\Tickets\Events\TicketClosed;
use SupportBay\Modules\Tickets\Events\TicketReopened;
use SupportBay\Modules\Tickets\Events\TicketResolved;

final class NotificationServiceProvider extends ServiceProvider {
  /** @var array<class-string, array<class-string>> */
  protected array $listeners = [
    TicketCreated::class => [
      SendTicketCreatedEmails::class,
    ],
    MessageCreated::class => [
      SendMessageCreatedEmail::class,
    ],
    TicketClosed::class => [
      SendTicketLifecycleEmail::class,
    ],
    TicketReopened::class => [
      SendTicketLifecycleEmail::class,
    ],
    TicketResolved::class => [
      SendTicketLifecycleEmail::class,
    ],
    TicketAssignmentChanged::class => [
      SendTicketAssignedEmail::class,
    ],
  ];

  public function register(Container $container): void {
    $container->singleton(
      NotificationChannel::class,
      WordPressEmailChannel::class,
    );
    $container->singleton(NotificationService::class);
    $container->singleton(NotificationRetryWorker::class);
    $container->singleton(NotificationCleanupWorker::class);
    $container->singleton(NotificationScheduler::class);
    $container->singleton(NotificationLogRepository::class);
    $container->singleton(NotificationPreferenceRepository::class);
    $container->singleton(NotificationRetentionRepository::class);
    $container->singleton(NotificationTemplateRepository::class);
    $container->singleton(NotificationPreferenceController::class);
    $container->singleton(NotificationTemplateController::class);
    $container->singleton(NotificationTemplateService::class);
    $container->singleton(NotificationPreferenceService::class);
    $container->singleton(NotificationRetentionService::class);
    $container->singleton(DefaultNotificationTemplates::class);
    $container->singleton(SendTicketCreatedEmails::class);
    $container->singleton(SendTicketLifecycleEmail::class);
    $container->singleton(SendTicketAssignedEmail::class);
    $container->singleton(SendMessageCreatedEmail::class);
  }

  public function boot(Container $container): void {
    parent::boot($container);

    add_action('rest_api_init', [
      $container->get(NotificationTemplateController::class),
      'registerRoutes',
    ]);
    add_action('rest_api_init', [
      $container->get(NotificationPreferenceController::class),
      'registerRoutes',
    ]);
    $container->get(NotificationScheduler::class)->register();
    $container->get(NotificationRetryWorker::class)->register();
    $container->get(NotificationCleanupWorker::class)->register();
  }
}
