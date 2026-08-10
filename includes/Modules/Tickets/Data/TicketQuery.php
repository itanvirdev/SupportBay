<?php

declare(strict_types=1);

namespace SupportBay\Modules\Tickets\Data;

final class TicketQuery {
  public function __construct(
    public readonly int $page = 1,
    public readonly int $perPage = 20,
    public readonly ?string $search = null,
    public readonly ?string $status = null,
    public readonly ?string $state = null,
    public readonly ?string $priority = null,
    public readonly ?int $assignedAgentId = null,
    public readonly bool $unassigned = false,
    public readonly ?int $customerId = null,
    public readonly ?int $departmentId = null,
    public readonly bool $needsReply = false,
    public readonly string $orderBy = 'updated_at',
    public readonly string $direction = 'DESC',
  ) {
  }
}
