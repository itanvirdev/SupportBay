<?php

declare(strict_types=1);

namespace SupportBay\Modules\Auth\Listeners;

use SupportBay\Common\Enums\AuthorType;
use SupportBay\Core\Events\Contracts\Listener;
use SupportBay\Modules\Activities\Enums\ActivityType;
use SupportBay\Modules\Activities\Services\ActivityService;
use SupportBay\Modules\Auth\Events\AuthTokenCreated;

final class LogAuthTokenCreatedActivity implements Listener {
  public function __construct(
    private readonly ActivityService $activities,
  ) {
  }

  /**
   * Handle event.
   */
  public function handle(object $event): void {
    if (! $event instanceof AuthTokenCreated) {
      return;
    }

    $this->activities->create([
      'actor_id'    => $event->userId,
      'actor_type'  => AuthorType::CUSTOMER,
      'event_type'  => ActivityType::MAGIC_LINK_GENERATED,
      'description' => 'Magic login link generated.',
      'payload'     => [
        'token_id'    => $event->tokenId,
        'type'        => $event->type->value,
        'redirect_to' => $event->redirectTo,
      ],
    ]);
  }
}
