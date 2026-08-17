<?php

declare(strict_types=1);

namespace SupportBay\Modules\CustomFields\Entities;

use SupportBay\Core\Entities\Entity;
use SupportBay\Modules\CustomFields\Enums\CustomFieldStatus;
use SupportBay\Modules\CustomFields\Enums\CustomFieldType;

final class CustomField extends Entity {
  /** @param string[] $options */
  public function __construct(
    private readonly int $id,
    private readonly string $name,
    private readonly string $slug,
    private readonly CustomFieldType $type,
    private readonly array $options,
    private readonly bool $required,
    private readonly bool $customerVisible,
    private readonly ?int $departmentId,
    private readonly CustomFieldStatus $status,
    private readonly int $sortOrder,
    private readonly string $createdAt,
    private readonly string $updatedAt,
  ) {}

  /** @return array<string, mixed> */
  public function toArray(): array {
    return [
      'id' => $this->id,
      'name' => $this->name,
      'slug' => $this->slug,
      'type' => $this->type->value,
      'options' => $this->options,
      'is_required' => $this->required,
      'customer_visible' => $this->customerVisible,
      'department_id' => $this->departmentId,
      'status' => $this->status->value,
      'sort_order' => $this->sortOrder,
      'created_at' => $this->createdAt,
      'updated_at' => $this->updatedAt,
    ];
  }

  public function id(): int { return $this->id; }
  public function name(): string { return $this->name; }
  public function slug(): string { return $this->slug; }
  public function type(): CustomFieldType { return $this->type; }
  /** @return string[] */
  public function options(): array { return $this->options; }
  public function isRequired(): bool { return $this->required; }
  public function isCustomerVisible(): bool { return $this->customerVisible; }
  public function departmentId(): ?int { return $this->departmentId; }
  public function status(): CustomFieldStatus { return $this->status; }
  public function sortOrder(): int { return $this->sortOrder; }
  public function createdAt(): string { return $this->createdAt; }
  public function updatedAt(): string { return $this->updatedAt; }

  public function isActive(): bool { return $this->status === CustomFieldStatus::ACTIVE; }
  public function appliesTo(int $departmentId): bool {
    return $this->departmentId === null || $this->departmentId === $departmentId;
  }
}
