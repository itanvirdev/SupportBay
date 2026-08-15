<?php

declare(strict_types=1);

namespace SupportBay\Modules\SavedReplies\Enums;

enum SavedReplyStatus: string {
  case ACTIVE = 'active';
  case INACTIVE = 'inactive';
}
