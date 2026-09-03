<?php

declare(strict_types=1);

namespace SupportBay\Modules\Notifications\Services;

use Throwable;
use SupportBay\Modules\Notifications\Contracts\NotificationChannel;
use SupportBay\Modules\Notifications\Data\NotificationData;

final class NotificationService {
  public function __construct(
    private readonly NotificationChannel $channel,
  ) {
  }

  public function send(NotificationData $notification): bool {
    if (! is_email($notification->recipient())) {
      return false;
    }

    try {
      return $this->channel->send($notification);
    } catch (Throwable) {
      return false;
    }
  }

  /**
   * Deliver immediately. The method name remains semantic for listeners while
   * SupportBay intentionally has no delivery-log queue in the initial release.
   */
  public function enqueue(NotificationData $notification): bool {
    return $this->send($notification);
  }
}
