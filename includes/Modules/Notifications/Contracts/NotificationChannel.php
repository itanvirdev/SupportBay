<?php

declare(strict_types=1);

namespace SupportBay\Modules\Notifications\Contracts;

use SupportBay\Modules\Notifications\Data\NotificationData;

interface NotificationChannel {
  public function channel(): string;

  public function provider(): ?string;

  public function send(NotificationData $notification): bool;
}
