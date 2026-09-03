<?php

declare(strict_types=1);

namespace SupportBay\Modules\SavedReplies\Repositories;

use SupportBay\Core\Database\Repository;
use SupportBay\Modules\SavedReplies\Database\SavedReplySchema;
use SupportBay\Modules\SavedReplies\Entities\SavedReply;
use SupportBay\Modules\SavedReplies\Enums\SavedReplyStatus;

final class SavedReplyRepository extends Repository {
  protected function table(): string { return SavedReplySchema::tableName(); }

  public function create(array $data): int {
    return $this->insert([
      'title' => $data['title'], 'content' => $data['content'], 'category' => $data['category'], 'status' => $data['status'],
      'created_by' => $data['created_by'], 'created_at' => $data['created_at'] ?? $this->now(),
      'updated_at' => $data['updated_at'] ?? $this->now(),
    ], ['%s', '%s', '%s', '%s', '%d', '%s', '%s']);
  }

  public function find(int $id): ?SavedReply { return $this->findById($id); }

  /** @return SavedReply[] */
  public function search(string $term = '', ?SavedReplyStatus $status = null, string $orderBy = 'title', ?string $category = null): array {
    $where = ['1=1'];
    $values = [];
    if ($term !== '') {
      $like = '%' . $this->db->esc_like($term) . '%';
      $where[] = '(title LIKE %s OR content LIKE %s OR category LIKE %s)';
      $values[] = $like;
      $values[] = $like;
      $values[] = $like;
    }
    if ($status) {
      $where[] = 'status = %s';
      $values[] = $status->value;
    }
    if ($category !== null) {
      $where[] = 'category = %s';
      $values[] = $category;
    }
    $order = match ($orderBy) {
      'usage' => 'usage_count DESC, last_used_at DESC, title ASC',
      'recent' => 'last_used_at DESC, usage_count DESC, title ASC',
      default => 'title ASC, id ASC',
    };
    $sql = "SELECT * FROM {$this->table()} WHERE " . implode(' AND ', $where) . " ORDER BY {$order}";
    $rows = $values ? $this->db->get_results($this->db->prepare($sql, ...$values), ARRAY_A) : $this->db->get_results($sql, ARRAY_A);
    return array_map(fn(array $row): SavedReply => $this->hydrate($row), $rows);
  }

  public function update(int $id, array $data): bool {
    $data['updated_at'] = $this->now();
    return $this->updateById($id, $data);
  }

  public function delete(int $id): bool { return $this->deleteById($id); }

  public function recordUsage(int $id, int $userId): bool {
    $result = $this->db->query($this->db->prepare(
      "UPDATE {$this->table()} SET usage_count = usage_count + 1, last_used_at = %s, last_used_by = %d WHERE id = %d AND status = %s",
      $this->now(), $userId, $id, SavedReplyStatus::ACTIVE->value,
    ));
    return $result === 1;
  }

  protected function hydrate(array $row): SavedReply {
    return new SavedReply(id: (int) $row['id'], title: (string) $row['title'], content: (string) $row['content'], category: ($row['category'] ?? '') !== '' ? (string) $row['category'] : null,
      status: SavedReplyStatus::from($row['status']), createdBy: (int) $row['created_by'],
      usageCount: (int) ($row['usage_count'] ?? 0), lastUsedAt: isset($row['last_used_at']) ? (string) $row['last_used_at'] : null,
      lastUsedBy: isset($row['last_used_by']) ? (int) $row['last_used_by'] : null,
      createdAt: (string) $row['created_at'], updatedAt: (string) $row['updated_at']);
  }
}
