<?php

declare(strict_types=1);

namespace SupportBay\Modules\Tags\Enums;

enum TagStatus: string {
  case ACTIVE = 'active';
  case INACTIVE = 'inactive';
}
