<?php

declare(strict_types=1);

namespace SupportBay\Modules\Departments\Entities;

use SupportBay\Core\Entities\Entity;
use SupportBay\Modules\Departments\Enums\DepartmentStatus;
use SupportBay\Modules\Tickets\Enums\TicketPriority;

final class Department extends Entity {

  public function __construct(
    private int $id,

    private string $name,
    private string $slug,

    private ?string $description,

    private DepartmentStatus $status,

    private int $sortOrder,

    private ?int $autoAssignAgentId,

    private TicketPriority $defaultPriority,

    private ?string $color,

    private ?string $icon,

    private ?string $metadata,

    private string $createdAt,
    private ?string $updatedAt,
  ) {
  }

  /**
   * Convert entity to array
   */
  public function toArray(): array {
    return [
      'id' => $this->id,
      'name' => $this->name,
      'slug' => $this->slug,
      'description' => $this->description,
      'status' => $this->status->value,
      'sort_order' => $this->sortOrder,
      'auto_assign_agent_id' => $this->autoAssignAgentId,
      'default_priority' => $this->defaultPriority->value,
      'color' => $this->color,
      'icon' => $this->icon,
      'metadata' => $this->metadata,
      'created_at' => $this->createdAt,
      'updated_at' => $this->updatedAt,
    ];
  }

  // ------------------------------------------------------------------
  // Getters
  // ------------------------------------------------------------------

  public function id(): int {
    return $this->id;
  }

  public function name(): string {
    return $this->name;
  }

  public function slug(): string {
    return $this->slug;
  }

  public function description(): ?string {
    return $this->description;
  }

  public function status(): DepartmentStatus {
    return $this->status;
  }

  public function sortOrder(): int {
    return $this->sortOrder;
  }

  public function autoAssignAgentId(): ?int {
    return $this->autoAssignAgentId;
  }

  public function defaultPriority(): TicketPriority {
    return $this->defaultPriority;
  }

  public function color(): ?string {
    return $this->color;
  }

  public function icon(): ?string {
    return $this->icon;
  }

  public function metadata(): ?string {
    return $this->metadata;
  }

  public function createdAt(): string {
    return $this->createdAt;
  }

  public function updatedAt(): ?string {
    return $this->updatedAt;
  }

  // ------------------------------------------------------------------
  // Domain Methods
  // ------------------------------------------------------------------

  /**
   * Is department active?
   */
  public function isActive(): bool {
    return $this->status->isActive();
  }

  /**
   * Is department inactive?
   */
  public function isInactive(): bool {
    return $this->status->isInactive();
  }

  /**
   * Has automatic agent assignment?
   */
  public function hasAutoAssignAgent(): bool {
    return $this->autoAssignAgentId !== null;
  }

  /**
   * Has description?
   */
  public function hasDescription(): bool {
    return !empty($this->description);
  }

  /**
   * Has custom color?
   */
  public function hasColor(): bool {
    return !empty($this->color);
  }

  /**
   * Has icon?
   */
  public function hasIcon(): bool {
    return !empty($this->icon);
  }

  /**
   * Has metadata?
   */
  public function hasMetadata(): bool {
    return !empty($this->metadata);
  }
}
