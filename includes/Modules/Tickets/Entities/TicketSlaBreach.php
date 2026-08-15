<?php

declare(strict_types=1);

namespace SupportBay\Modules\Tickets\Entities;

use SupportBay\Core\Entities\Entity;

final class TicketSlaBreach extends Entity {
  public function __construct(
    private int $id,
    private int $ticketId,
    private string $metric,
    private int $targetMinutes,
    private string $breachedAt,
    private string $createdAt,
  ) {
  }

  public function toArray(): array {
    return ['id'=>$this->id,'ticket_id'=>$this->ticketId,'metric'=>$this->metric,'target_minutes'=>$this->targetMinutes,'breached_at'=>$this->breachedAt,'created_at'=>$this->createdAt];
  }

  public function id(): int { return $this->id; }
  public function ticketId(): int { return $this->ticketId; }
  public function metric(): string { return $this->metric; }
  public function targetMinutes(): int { return $this->targetMinutes; }
  public function breachedAt(): string { return $this->breachedAt; }
  public function createdAt(): string { return $this->createdAt; }
}
