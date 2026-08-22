<?php

declare(strict_types=1);

namespace SupportBay\Modules\Tags\Repositories;

use SupportBay\Core\Database\Repository;
use SupportBay\Modules\Tags\Database\TagSchema;
use SupportBay\Modules\Tags\Database\TicketTagSchema;
use SupportBay\Modules\Tags\Entities\Tag;
use SupportBay\Modules\Tags\Enums\TagStatus;

final class TagRepository extends Repository {
  protected function table(): string {
    return TagSchema::tableName();
  }

  /** @param array<string, mixed> $data */
  public function create(array $data): int {
    return $this->insert([
      'name' => $data['name'],
      'slug' => $data['slug'],
      'color' => $data['color'],
      'status' => $data['status'],
      'created_at' => $this->now(),
      'updated_at' => $this->now(),
    ], ['%s', '%s', '%s', '%s', '%s', '%s']);
  }

  public function find(int $id): ?Tag { return $this->findById($id); }
  public function findBySlug(string $slug): ?Tag { return $this->first(['slug' => $slug]); }

  /** @return Tag[] */
  public function all(): array { return $this->findAll('name'); }

  /** @return Tag[] */
  public function active(): array {
    return $this->findWhere(['status' => TagStatus::ACTIVE->value], 'name');
  }

  /** @param array<string, mixed> $data */
  public function update(int $id, array $data): bool {
    $data['updated_at'] = $this->now();
    return $this->updateById($id, $data);
  }

  public function delete(int $id): bool { return $this->deleteById($id); }

  public function attach(int $ticketId, int $tagId, ?int $actorId): bool {
    $result = $this->db->query($this->db->prepare(
      'INSERT IGNORE INTO ' . TicketTagSchema::tableName()
        . ' (ticket_id, tag_id, assigned_by, created_at) VALUES (%d, %d, NULLIF(%d, 0), %s)',
      $ticketId,
      $tagId,
      $actorId ?? 0,
      $this->now(),
    ));

    return $result !== false;
  }

  public function detach(int $ticketId, int $tagId): bool {
    return $this->db->delete(
      TicketTagSchema::tableName(),
      ['ticket_id' => $ticketId, 'tag_id' => $tagId],
      ['%d', '%d'],
    ) !== false;
  }

  public function isAttached(int $ticketId, int $tagId): bool {
    return (bool) $this->db->get_var($this->db->prepare(
      'SELECT 1 FROM ' . TicketTagSchema::tableName()
        . ' WHERE ticket_id = %d AND tag_id = %d LIMIT 1',
      $ticketId,
      $tagId,
    ));
  }

  /** @return Tag[] */
  public function findByTicket(int $ticketId): array {
    $rows = $this->db->get_results($this->db->prepare(
      'SELECT tags.* FROM ' . $this->table() . ' tags INNER JOIN '
        . TicketTagSchema::tableName() . ' links ON links.tag_id = tags.id '
        . 'WHERE links.ticket_id = %d ORDER BY tags.name ASC',
      $ticketId,
    ), ARRAY_A);

    return array_map(fn(array $row): Tag => $this->hydrate($row), $rows);
  }

  /** @param int[] $ticketIds @return array<int, Tag[]> */
  public function findByTickets(array $ticketIds): array {
    $ids = array_values(array_unique(array_filter(array_map('absint', $ticketIds))));
    if ($ids === []) { return []; }
    $placeholders = implode(', ', array_fill(0, count($ids), '%d'));
    $rows = $this->db->get_results($this->db->prepare(
      'SELECT links.ticket_id, tags.* FROM ' . $this->table() . ' tags INNER JOIN '
        . TicketTagSchema::tableName() . " links ON links.tag_id = tags.id WHERE links.ticket_id IN ({$placeholders}) ORDER BY tags.name ASC",
      ...$ids,
    ), ARRAY_A);
    $result = [];
    foreach ($rows as $row) {
      $ticketId = (int) $row['ticket_id'];
      unset($row['ticket_id']);
      $result[$ticketId][] = $this->hydrate($row);
    }
    return $result;
  }

  public function assignmentCount(int $tagId): int {
    return (int) $this->db->get_var($this->db->prepare(
      'SELECT COUNT(*) FROM ' . TicketTagSchema::tableName() . ' WHERE tag_id = %d',
      $tagId,
    ));
  }
  public function deleteAssignmentsForTicket(int $ticketId):int{return(int)$this->db->delete(TicketTagSchema::tableName(),['ticket_id'=>$ticketId],['%d']);}

  protected function hydrate(array $row): Tag {
    return new Tag(
      id: (int) $row['id'],
      name: (string) $row['name'],
      slug: (string) $row['slug'],
      color: $row['color'] !== null ? (string) $row['color'] : null,
      status: TagStatus::from($row['status']),
      createdAt: (string) $row['created_at'],
      updatedAt: (string) $row['updated_at'],
    );
  }
}
