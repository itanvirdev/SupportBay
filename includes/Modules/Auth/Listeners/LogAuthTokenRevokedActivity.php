<?php

declare(strict_types=1);

namespace SupportBay\Modules\Auth\Listeners;

use SupportBay\Common\Enums\AuthorType;
use SupportBay\Core\Events\Contracts\Listener;
use SupportBay\Modules\Activities\Enums\ActivityType;
use SupportBay\Modules\Activities\Services\ActivityService;
use SupportBay\Modules\Auth\Events\AuthTokenRevoked;

final class LogAuthTokenRevokedActivity implements Listener {
  public function __construct(
    private readonly ActivityService $activities,
  ) {
  }

  /**
   * Handle event.
   */
  public function handle(object $event): void {
    if (! $event instanceof AuthTokenRevoked) {
      return;
    }

    $this->activities->create([
      'actor_id'    => $event->userId,
      'actor_type'  => AuthorType::CUSTOMER,
      'event_type'  => ActivityType::MAGIC_LINK_REVOKED,
      'description' => 'Magic login token revoked.',
      'payload'     => [
        'token_id' => $event->tokenId,
        'type'     => $event->type->value,
      ],
    ]);
  }
}
