<?php

declare(strict_types=1);

namespace SupportBay\Modules\Tags\Entities;

use SupportBay\Core\Entities\Entity;
use SupportBay\Modules\Tags\Enums\TagStatus;

final class Tag extends Entity {
  public function __construct(
    private readonly int $id,
    private readonly string $name,
    private readonly string $slug,
    private readonly ?string $color,
    private readonly string $showOn,
    private readonly TagStatus $status,
    private readonly string $createdAt,
    private readonly string $updatedAt,
  ) {
  }

  /** @return array<string, mixed> */
  public function toArray(): array {
    return [
      'id' => $this->id,
      'name' => $this->name,
      'slug' => $this->slug,
      'color' => $this->color,
      'show_on' => $this->showOn,
      'status' => $this->status->value,
      'created_at' => $this->createdAt,
      'updated_at' => $this->updatedAt,
    ];
  }

  public function id(): int { return $this->id; }
  public function name(): string { return $this->name; }
  public function slug(): string { return $this->slug; }
  public function color(): ?string { return $this->color; }
  public function showOn(): string { return $this->showOn; }
  public function status(): TagStatus { return $this->status; }
  public function createdAt(): string { return $this->createdAt; }
  public function updatedAt(): string { return $this->updatedAt; }

  public function isActive(): bool {
    return $this->status === TagStatus::ACTIVE;
  }

  public function isCustomerVisible(): bool {
    return $this->showOn === 'both';
  }
}
