<?php

declare(strict_types=1);

namespace SupportBay\Modules\Tickets\Repositories;

use SupportBay\Core\Database\Repository;
use SupportBay\Modules\Tickets\Database\TicketSchema;
use SupportBay\Modules\Tickets\Database\TicketSlaBreachSchema;
use SupportBay\Modules\Tickets\Entities\TicketSlaBreach;

final class TicketSlaBreachRepository extends Repository {
  public const FIRST_RESPONSE = 'first_response';

  protected function table(): string { return TicketSlaBreachSchema::tableName(); }

  /** @param array<string, int> $targets @return array<int, array{ticket_id:int,target_minutes:int}> */
  public function findUnrecordedFirstResponseBreaches(string $now, array $targets, int $limit): array {
    $target = "CASE t.priority
      WHEN 'urgent' THEN " . $this->target($targets, 'urgent', 60) . "
      WHEN 'high' THEN " . $this->target($targets, 'high', 240) . "
      WHEN 'medium' THEN " . $this->target($targets, 'medium', 480) . "
      ELSE " . $this->target($targets, 'normal', 1440) . ' END';
    $rows = $this->db->get_results($this->db->prepare(
      "SELECT t.id ticket_id, {$target} target_minutes
       FROM " . TicketSchema::tableName() . " t
       LEFT JOIN {$this->table()} b ON b.ticket_id=t.id AND b.metric=%s
       WHERE b.id IS NULL AND t.first_response_at IS NULL
         AND t.state='active' AND t.status NOT IN ('resolved','closed')
         AND TIMESTAMPDIFF(MINUTE,t.created_at,%s) > {$target}
       ORDER BY t.created_at ASC, t.id ASC LIMIT %d",
      self::FIRST_RESPONSE,
      $now,
      max(1, min(100, $limit)),
    ), ARRAY_A);
    return array_map(static fn(array $row): array => ['ticket_id'=>(int)$row['ticket_id'],'target_minutes'=>(int)$row['target_minutes']], $rows);
  }

  public function claim(int $ticketId, int $targetMinutes, string $breachedAt): ?TicketSlaBreach {
    $result = $this->db->query($this->db->prepare(
      "INSERT IGNORE INTO {$this->table()} (ticket_id,metric,target_minutes,breached_at,created_at) VALUES (%d,%s,%d,%s,%s)",
      $ticketId, self::FIRST_RESPONSE, $targetMinutes, $breachedAt, $this->now(),
    ));
    if ($result !== 1) { return null; }
    $breach = $this->findById((int) $this->db->insert_id);
    return $breach instanceof TicketSlaBreach ? $breach : null;
  }

  public function findByTicket(int $ticketId): ?TicketSlaBreach {
    $row = $this->db->get_row($this->db->prepare("SELECT * FROM {$this->table()} WHERE ticket_id=%d AND metric=%s", $ticketId, self::FIRST_RESPONSE), ARRAY_A);
    return $row ? $this->hydrate($row) : null;
  }

  public function deleteByTicket(int $ticketId): int {
    return (int) $this->db->delete($this->table(), ['ticket_id'=>$ticketId], ['%d']);
  }

  protected function hydrate(array $row): object {
    return new TicketSlaBreach((int)$row['id'],(int)$row['ticket_id'],(string)$row['metric'],(int)$row['target_minutes'],(string)$row['breached_at'],(string)$row['created_at']);
  }

  /** @param array<string, int> $targets */
  private function target(array $targets, string $priority, int $default): int {
    return min(10080, max(15, (int) ($targets[$priority] ?? $default)));
  }
}
