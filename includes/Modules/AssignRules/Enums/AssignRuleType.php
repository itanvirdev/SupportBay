<?php

declare(strict_types=1);

namespace SupportBay\Modules\AssignRules\Enums;

enum AssignRuleType: string {
  case ROLE = 'role';
  case AGENT = 'agent';
  case NOTIFY = 'notify';
}
