<?php

declare(strict_types=1);

namespace SupportBay\Modules\Tickets\Services;

use SupportBay\Core\Authorization\CapabilityManager;
use SupportBay\Modules\Tickets\Entities\Ticket;

final class TicketAccessPolicy {
  public function canView(Ticket $ticket, ?int $userId = null): bool {
    $userId ??= get_current_user_id();
    if ($userId <= 0 || ! user_can($userId, CapabilityManager::VIEW_TICKETS)) { return false; }
    if (user_can($userId, CapabilityManager::VIEW_ALL_TICKETS)) { return true; }
    if ($ticket->assignedAgentId() === $userId) { return true; }
    return $ticket->assignedAgentId() === null
      && user_can($userId, CapabilityManager::VIEW_UNASSIGNED_TICKETS);
  }

  public function queueScopeAgentId(?int $userId = null): ?int {
    $userId ??= get_current_user_id();
    return user_can($userId, CapabilityManager::VIEW_ALL_TICKETS) ? null : $userId;
  }

  public function canViewUnassigned(?int $userId = null): bool {
    $userId ??= get_current_user_id();
    return user_can($userId, CapabilityManager::VIEW_ALL_TICKETS)
      || user_can($userId, CapabilityManager::VIEW_UNASSIGNED_TICKETS);
  }
}
