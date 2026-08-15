<?php

declare(strict_types=1);

namespace SupportBay\Modules\Notifications\Enums;

enum NotificationTemplateStatus: string {
  case ACTIVE = 'active';
  case INACTIVE = 'inactive';

  public function isActive(): bool {
    return $this === self::ACTIVE;
  }
}
