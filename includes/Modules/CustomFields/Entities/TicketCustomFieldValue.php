<?php

declare(strict_types=1);

namespace SupportBay\Modules\CustomFields\Entities;

use SupportBay\Core\Entities\Entity;

final class TicketCustomFieldValue extends Entity {
  public function __construct(
    private readonly int $id,
    private readonly int $ticketId,
    private readonly int $fieldId,
    private readonly string $value,
    private readonly ?int $updatedBy,
    private readonly string $createdAt,
    private readonly string $updatedAt,
  ) {}

  /** @return array<string, mixed> */
  public function toArray(): array {
    return [
      'id' => $this->id,
      'ticket_id' => $this->ticketId,
      'field_id' => $this->fieldId,
      'value' => $this->value,
      'updated_by' => $this->updatedBy,
      'created_at' => $this->createdAt,
      'updated_at' => $this->updatedAt,
    ];
  }

  public function id(): int { return $this->id; }
  public function ticketId(): int { return $this->ticketId; }
  public function fieldId(): int { return $this->fieldId; }
  public function value(): string { return $this->value; }
  public function updatedBy(): ?int { return $this->updatedBy; }
  public function createdAt(): string { return $this->createdAt; }
  public function updatedAt(): string { return $this->updatedAt; }
}
