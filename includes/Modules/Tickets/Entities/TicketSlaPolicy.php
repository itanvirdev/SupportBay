<?php

declare(strict_types=1);

namespace SupportBay\Modules\Tickets\Entities;

use SupportBay\Core\Entities\Entity;

final class TicketSlaPolicy extends Entity {
  /** @param array<string, int> $firstResponseMinutes */
  public function __construct(
    private bool $enabled,
    private array $firstResponseMinutes,
  ) {
  }

  public function toArray(): array {
    return ['enabled' => $this->enabled, 'first_response_minutes' => $this->firstResponseMinutes];
  }

  public function enabled(): bool { return $this->enabled; }

  /** @return array<string, int> */
  public function firstResponseMinutes(): array { return $this->firstResponseMinutes; }
}
