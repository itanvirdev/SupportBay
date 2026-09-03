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
    private readonly ?string $placeholder,
    private readonly bool $required,
    private readonly string $formLocation,
    private readonly string $audience,
    private readonly array $categoryIds,
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
      'placeholder' => $this->placeholder,
      'is_required' => $this->required,
      'form_location' => $this->formLocation,
      'audience' => $this->audience,
      'category_ids' => $this->categoryIds,
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
  public function placeholder(): ?string { return $this->placeholder; }
  public function isRequired(): bool { return $this->required; }
  public function formLocation(): string { return $this->formLocation; }
  public function audience(): string { return $this->audience; }
  /** @return int[] */
  public function categoryIds(): array { return $this->categoryIds; }
  public function status(): CustomFieldStatus { return $this->status; }
  public function sortOrder(): int { return $this->sortOrder; }
  public function createdAt(): string { return $this->createdAt; }
  public function updatedAt(): string { return $this->updatedAt; }

  public function isActive(): bool { return $this->status === CustomFieldStatus::ACTIVE; }
  public function isCustomerVisible(): bool { return $this->audience === 'both'; }
  public function appliesToCategory(?int $categoryId): bool {
    return $this->categoryIds === [] || ($categoryId !== null && in_array($categoryId, $this->categoryIds, true));
  }
}
