<?php

declare(strict_types=1);

namespace SupportBay\Modules\Notifications\Channels;

use SupportBay\Modules\Notifications\Contracts\NotificationChannel;
use SupportBay\Modules\Notifications\Data\NotificationData;

final class WordPressEmailChannel implements NotificationChannel {
  public function send(NotificationData $notification): bool {
    return wp_mail(
      $notification->recipient(),
      $notification->subject(),
      $notification->content(),
      $notification->headers(),
    );
  }
}
