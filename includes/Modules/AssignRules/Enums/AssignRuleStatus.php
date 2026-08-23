<?php

declare(strict_types=1);

namespace SupportBay\Modules\AssignRules\Enums;

enum AssignRuleStatus: string {
  case ACTIVE = 'active';
  case INACTIVE = 'inactive';
}
