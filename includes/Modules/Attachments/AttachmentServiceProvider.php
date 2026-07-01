<?php

declare(strict_types=1);

namespace SupportBay\Modules\Attachments;

use SupportBay\Core\Container\Container;
use SupportBay\Core\Foundation\ServiceProvider;
use SupportBay\Core\Events\EventDispatcher;
use SupportBay\Modules\Attachments\Events\AttachmentDeleted;
use SupportBay\Modules\Attachments\Events\AttachmentUploaded;
use SupportBay\Modules\Attachments\Listeners\LogAttachmentDeletedActivity;
use SupportBay\Modules\Attachments\Listeners\LogAttachmentUploadedActivity;
use SupportBay\Modules\Attachments\Repositories\AttachmentRepository;
use SupportBay\Modules\Attachments\Services\AttachmentService;

final class AttachmentServiceProvider extends ServiceProvider {

  /**
   * Event listeners.
   *
   * @var array<class-string, array<class-string>>
   */
  protected array $listeners = [
    AttachmentUploaded::class => [
      LogAttachmentUploadedActivity::class,
    ],

    AttachmentDeleted::class => [
      LogAttachmentDeletedActivity::class,
    ],
  ];

  /**
   * Register services.
   */
  public function register(Container $container): void {
    $container->singleton(AttachmentRepository::class);

    $container->singleton(AttachmentService::class);
  }
}
