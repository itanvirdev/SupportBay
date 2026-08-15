<?php

declare(strict_types=1);

namespace SupportBay\Modules\Tickets\Data;

final readonly class TicketMetricQuery {
  public function __construct(
    public string $dateFrom,
    public string $dateTo,
    public ?int $departmentId = null,
    public ?int $assignedAgentId = null,
    public ?string $priority = null,
  ) {
  }
}
