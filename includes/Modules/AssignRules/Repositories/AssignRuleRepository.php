<?php

declare(strict_types=1);

namespace SupportBay\Modules\AssignRules\Repositories;

use SupportBay\Core\Database\Repository;
use SupportBay\Modules\AssignRules\Database\AssignRuleSchema;
use SupportBay\Modules\AssignRules\Entities\AssignRule;
use SupportBay\Modules\AssignRules\Enums\AssignRuleStatus;
use SupportBay\Modules\AssignRules\Enums\AssignRuleType;

final class AssignRuleRepository extends Repository {
  protected function table(): string { return AssignRuleSchema::tableName(); }

  /** @param array<string, mixed> $data */
  public function create(array $data): int {
    return $this->insert([
      'rule_type' => $data['rule_type'],
      'target_role' => $data['target_role'],
      'target_agent_id' => $data['target_agent_id'],
      'category_ids' => wp_json_encode($data['category_ids']),
      'status' => $data['status'],
      'created_at' => $this->now(),
      'updated_at' => $this->now(),
    ], ['%s', '%s', '%d', '%s', '%s', '%s', '%s']);
  }

  public function find(int $id): ?AssignRule { return $this->findById($id); }
  /** @return AssignRule[] */
  public function all(): array { return $this->findAll('id', 'DESC'); }
  /** @return AssignRule[] */
  public function active(): array { return $this->findWhere(['status' => AssignRuleStatus::ACTIVE->value], 'id'); }
  /** @param array<string, mixed> $data */
  public function update(int $id, array $data): bool {
    if (isset($data['category_ids'])) { $data['category_ids'] = wp_json_encode($data['category_ids']); }
    $data['updated_at'] = $this->now();
    return $this->updateById($id, $data);
  }
  public function delete(int $id): bool { return $this->deleteById($id); }

  protected function hydrate(array $row): AssignRule {
    $categoryIds = json_decode((string) $row['category_ids'], true);
    return new AssignRule(
      id: (int) $row['id'],
      type: AssignRuleType::from((string) $row['rule_type']),
      targetRole: $row['target_role'] !== null ? (string) $row['target_role'] : null,
      targetAgentId: $row['target_agent_id'] !== null ? (int) $row['target_agent_id'] : null,
      categoryIds: is_array($categoryIds) ? array_values(array_map('absint', $categoryIds)) : [],
      status: AssignRuleStatus::from((string) $row['status']),
      createdAt: (string) $row['created_at'],
      updatedAt: (string) $row['updated_at'],
    );
  }
}
