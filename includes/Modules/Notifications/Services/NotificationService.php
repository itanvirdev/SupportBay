<?php

declare(strict_types=1);

namespace SupportBay\Modules\Notifications\Services;

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

    return $this->channel->send($notification);
  }
}
