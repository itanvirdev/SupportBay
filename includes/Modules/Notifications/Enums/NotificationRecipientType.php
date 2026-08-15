<?php

declare(strict_types=1);

namespace SupportBay\Modules\Notifications\Enums;

enum NotificationRecipientType: string {
  case CUSTOMER = 'customer';
  case AGENT = 'agent';
  case MANAGER = 'manager';
}
