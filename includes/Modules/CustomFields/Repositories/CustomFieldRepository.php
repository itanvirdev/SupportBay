<?php

declare(strict_types=1);

namespace SupportBay\Modules\CustomFields\Repositories;

use SupportBay\Core\Database\Repository;
use SupportBay\Modules\CustomFields\Database\CustomFieldSchema;
use SupportBay\Modules\CustomFields\Database\TicketCustomFieldValueSchema;
use SupportBay\Modules\CustomFields\Entities\CustomField;
use SupportBay\Modules\CustomFields\Entities\TicketCustomFieldValue;
use SupportBay\Modules\CustomFields\Enums\CustomFieldStatus;
use SupportBay\Modules\CustomFields\Enums\CustomFieldType;

final class CustomFieldRepository extends Repository {
  protected function table(): string { return CustomFieldSchema::tableName(); }

  /** @param array<string, mixed> $data */
  public function create(array $data): int {
    return $this->insert([
      ...$data,
      'created_at' => $this->now(),
      'updated_at' => $this->now(),
    ]);
  }

  public function find(int $id): ?CustomField { return $this->findById($id); }
  public function findBySlug(string $slug): ?CustomField { return $this->first(['slug' => $slug]); }
  /** @return CustomField[] */
  public function all(): array { return $this->findAll('sort_order', 'ASC'); }
  /** @return CustomField[] */
  public function active(): array { return $this->findWhere(['status' => CustomFieldStatus::ACTIVE->value], 'sort_order'); }
  /** @param array<string, mixed> $data */
  public function update(int $id, array $data): bool {
    $data['updated_at'] = $this->now();
    return $this->updateById($id, $data);
  }
  public function delete(int $id): bool { return $this->deleteById($id); }

  public function valueCount(int $fieldId): int {
    return (int) $this->db->get_var($this->db->prepare(
      'SELECT COUNT(*) FROM ' . TicketCustomFieldValueSchema::tableName() . ' WHERE field_id = %d',
      $fieldId,
    ));
  }

  public function saveValue(int $ticketId, int $fieldId, string $value, ?int $actorId): bool {
    $now = $this->now();
    $result = $this->db->query($this->db->prepare(
      'INSERT INTO ' . TicketCustomFieldValueSchema::tableName()
        . ' (ticket_id, field_id, value, updated_by, created_at, updated_at) VALUES (%d, %d, %s, NULLIF(%d, 0), %s, %s) '
        . 'ON DUPLICATE KEY UPDATE value = VALUES(value), updated_by = VALUES(updated_by), updated_at = VALUES(updated_at)',
      $ticketId,
      $fieldId,
      $value,
      $actorId ?? 0,
      $now,
      $now,
    ));
    return $result !== false;
  }

  public function deleteValue(int $ticketId, int $fieldId): bool {
    return $this->db->delete(
      TicketCustomFieldValueSchema::tableName(),
      ['ticket_id' => $ticketId, 'field_id' => $fieldId],
      ['%d', '%d'],
    ) !== false;
  }

  /** @return TicketCustomFieldValue[] */
  public function valuesForTicket(int $ticketId): array {
    $rows = $this->db->get_results($this->db->prepare(
      'SELECT * FROM ' . TicketCustomFieldValueSchema::tableName() . ' WHERE ticket_id = %d ORDER BY field_id ASC',
      $ticketId,
    ), ARRAY_A);
    return array_map([$this, 'hydrateValue'], $rows);
  }

  protected function hydrate(array $row): CustomField {
    $options = json_decode((string) ($row['options'] ?? '[]'), true);
    return new CustomField(
      id: (int) $row['id'],
      name: (string) $row['name'],
      slug: (string) $row['slug'],
      type: CustomFieldType::from($row['type']),
      options: is_array($options) ? array_values(array_map('strval', $options)) : [],
      required: (bool) $row['is_required'],
      customerVisible: (bool) $row['customer_visible'],
      departmentId: $row['department_id'] !== null ? (int) $row['department_id'] : null,
      status: CustomFieldStatus::from($row['status']),
      sortOrder: (int) $row['sort_order'],
      createdAt: (string) $row['created_at'],
      updatedAt: (string) $row['updated_at'],
    );
  }

  /** @param array<string, mixed> $row */
  private function hydrateValue(array $row): TicketCustomFieldValue {
    return new TicketCustomFieldValue(
      id: (int) $row['id'],
      ticketId: (int) $row['ticket_id'],
      fieldId: (int) $row['field_id'],
      value: (string) $row['value'],
      updatedBy: $row['updated_by'] !== null ? (int) $row['updated_by'] : null,
      createdAt: (string) $row['created_at'],
      updatedAt: (string) $row['updated_at'],
    );
  }
}
