<?php

declare(strict_types=1);

namespace SupportBay\Modules\CustomFields\Enums;

enum CustomFieldStatus: string {
  case ACTIVE = 'active';
  case INACTIVE = 'inactive';
}
