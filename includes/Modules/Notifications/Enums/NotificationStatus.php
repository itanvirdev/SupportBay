<?php

declare(strict_types=1);

namespace SupportBay\Modules\Notifications\Enums;

enum NotificationStatus: string {
  case PENDING = 'pending';
  case PROCESSING = 'processing';
  case SENT = 'sent';
  case DELIVERED = 'delivered';
  case FAILED = 'failed';
  case CANCELLED = 'cancelled';

  public function canRetry(): bool {
    return $this === self::FAILED;
  }

  public function isSuccessful(): bool {
    return in_array($this, [self::SENT, self::DELIVERED], true);
  }
}
