<?php

declare(strict_types=1);

namespace SupportBay\Modules\Admin\Repositories;

use SupportBay\Core\Database\Repository;
use SupportBay\Modules\Admin\Data\CustomerDirectoryItem;
use SupportBay\Modules\Admin\Data\CustomerDirectoryQuery;
use SupportBay\Modules\Customers\Database\CustomerSchema;
use SupportBay\Modules\Tickets\Database\TicketSchema;
use SupportBay\Modules\Verifications\Database\PurchaseVerificationSchema;

final class CustomerDirectoryRepository extends Repository {
  protected function table(): string {
    return CustomerSchema::tableName();
  }

  protected function hydrate(array $row): CustomerDirectoryItem {
    return new CustomerDirectoryItem($row);
  }

  /** @return array{items: CustomerDirectoryItem[], total: int} */
  public function search(CustomerDirectoryQuery $query): array {
    $customerTable = $this->table();
    $ticketTable = TicketSchema::tableName();
    $verificationTable = PurchaseVerificationSchema::tableName();
    $userTable = $this->db->users;
    $clauses = [];
    $values = [];

    if ($query->state !== null) {
      $clauses[] = 'c.state = %s';
      $values[] = $query->state;
    }

    if ($query->source !== null) {
      $clauses[] = 'c.source = %s';
      $values[] = $query->source;
    }

    if ($query->search !== null && $query->search !== '') {
      $like = '%' . $this->db->esc_like($query->search) . '%';
      $clauses[] = '(u.display_name LIKE %s OR u.user_email LIKE %s OR c.company LIKE %s OR c.phone LIKE %s)';
      array_push($values, $like, $like, $like, $like);
    }

    $where = $clauses ? 'WHERE ' . implode(' AND ', $clauses) : '';
    $countSql = "SELECT COUNT(*) FROM {$customerTable} c INNER JOIN {$userTable} u ON u.ID = c.user_id {$where}";
    $total = (int) ($values
      ? $this->db->get_var($this->db->prepare($countSql, ...$values))
      : $this->db->get_var($countSql));
    $ticketAggregate = "(SELECT customer_id, COUNT(*) ticket_count, SUM(state = 'active' AND status <> 'closed') open_ticket_count, MAX(updated_at) last_ticket_at FROM {$ticketTable} GROUP BY customer_id) tq";
    $purchaseAggregate = "(SELECT customer_id, COUNT(*) purchase_count, SUM(verification_status = 'verified') verified_purchase_count FROM {$verificationTable} GROUP BY customer_id) vq";
    $orderBy = match ($query->orderBy) {
      'name' => 'u.display_name',
      'tickets' => 'COALESCE(tq.ticket_count, 0)',
      'purchases' => 'COALESCE(vq.purchase_count, 0)',
      'created_at' => 'c.created_at',
      default => 'COALESCE(tq.last_ticket_at, c.last_login_at, c.updated_at)',
    };
    $direction = strtoupper($query->direction) === 'ASC' ? 'ASC' : 'DESC';
    $sql = "SELECT c.*, u.display_name, u.user_email email, COALESCE(tq.ticket_count, 0) ticket_count, COALESCE(tq.open_ticket_count, 0) open_ticket_count, COALESCE(vq.purchase_count, 0) purchase_count, COALESCE(vq.verified_purchase_count, 0) verified_purchase_count, COALESCE(tq.last_ticket_at, c.last_login_at, c.updated_at) last_activity_at FROM {$customerTable} c INNER JOIN {$userTable} u ON u.ID = c.user_id LEFT JOIN {$ticketAggregate} ON tq.customer_id = c.id LEFT JOIN {$purchaseAggregate} ON vq.customer_id = c.id {$where} ORDER BY {$orderBy} {$direction}, c.id DESC LIMIT %d OFFSET %d";
    $rows = $this->db->get_results($this->db->prepare(
      $sql,
      ...[...$values, $query->perPage, ($query->page - 1) * $query->perPage],
    ), ARRAY_A);

    return [
      'items' => array_map(fn(array $row): CustomerDirectoryItem => $this->hydrate($row), $rows),
      'total' => $total,
    ];
  }
}
