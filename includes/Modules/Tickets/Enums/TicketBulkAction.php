<?php

declare(strict_types=1);

namespace SupportBay\Modules\Tickets\Enums;

enum TicketBulkAction: string {
  case ASSIGNMENT = 'assignment';
  case DEPARTMENT = 'department';
  case CATEGORY = 'category';
  case TAG_ADD = 'tag_add';
  case TAG_REMOVE = 'tag_remove';
  case PRIORITY = 'priority';
  case STATE = 'state';
}
