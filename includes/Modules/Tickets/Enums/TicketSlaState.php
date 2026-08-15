<?php

declare(strict_types=1);

namespace SupportBay\Modules\Tickets\Enums;

enum TicketSlaState: string {
  case DISABLED = 'disabled';
  case MET = 'met';
  case ON_TRACK = 'on_track';
  case DUE_SOON = 'due_soon';
  case BREACHED = 'breached';
}
