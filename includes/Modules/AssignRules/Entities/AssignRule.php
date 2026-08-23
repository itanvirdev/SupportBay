<?php

declare(strict_types=1);

namespace SupportBay\Modules\AssignRules\Entities;

use SupportBay\Core\Entities\Entity;
use SupportBay\Modules\AssignRules\Enums\AssignRuleStatus;
use SupportBay\Modules\AssignRules\Enums\AssignRuleType;

final class AssignRule extends Entity {
  /** @param int[] $categoryIds */
  public function __construct(
    private readonly int $id,
    private readonly AssignRuleType $type,
    private readonly ?string $targetRole,
    private readonly ?int $targetAgentId,
    private readonly array $categoryIds,
    private readonly AssignRuleStatus $status,
    private readonly string $createdAt,
    private readonly string $updatedAt,
  ) {}

  public function toArray(): array {
    return [
      'id' => $this->id,
      'rule_type' => $this->type->value,
      'target_role' => $this->targetRole,
      'target_agent_id' => $this->targetAgentId,
      'category_ids' => $this->categoryIds,
      'all_categories' => $this->appliesToAllCategories(),
      'status' => $this->status->value,
      'created_at' => $this->createdAt,
      'updated_at' => $this->updatedAt,
    ];
  }

  public function id(): int { return $this->id; }
  public function type(): AssignRuleType { return $this->type; }
  public function targetRole(): ?string { return $this->targetRole; }
  public function targetAgentId(): ?int { return $this->targetAgentId; }
  /** @return int[] */
  public function categoryIds(): array { return $this->categoryIds; }
  public function status(): AssignRuleStatus { return $this->status; }
  public function createdAt(): string { return $this->createdAt; }
  public function updatedAt(): string { return $this->updatedAt; }

  public function isActive(): bool { return $this->status === AssignRuleStatus::ACTIVE; }
  public function appliesToAllCategories(): bool { return $this->categoryIds === []; }
  public function matchesCategory(?int $categoryId): bool {
    return $this->appliesToAllCategories()
      || ($categoryId !== null && in_array($categoryId, $this->categoryIds, true));
  }
}
