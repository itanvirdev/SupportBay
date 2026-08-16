<?php

declare(strict_types=1);

namespace SupportBay\Modules\Tickets\Repositories;

use SupportBay\Core\Database\Repository;
use SupportBay\Common\Enums\AuthorType;
use SupportBay\Common\Enums\SourceType;
use SupportBay\Modules\Tickets\Enums\TicketPriority;
use SupportBay\Modules\Tickets\Enums\TicketState;
use SupportBay\Modules\Tickets\Enums\TicketStatus;
use SupportBay\Modules\Tickets\Database\TicketSchema;
use SupportBay\Modules\Tickets\Entities\Ticket;
use SupportBay\Modules\Tickets\Data\TicketQuery;
use SupportBay\Modules\Tickets\Data\TicketQueueItem;
use SupportBay\Modules\Tickets\Data\TicketMetricQuery;
use SupportBay\Modules\Messages\Database\MessageSchema;
use SupportBay\Modules\Customers\Database\CustomerSchema;
use SupportBay\Modules\Departments\Database\DepartmentSchema;
use SupportBay\Modules\Categories\Database\CategorySchema;

final class TicketRepository extends Repository {

  /**
   * Table
   */
  protected function table(): string {
    return TicketSchema::tableName();
  }


  /**
   * Create a new ticket
   */
  public function create(array $data): int {
    return $this->insert(
      [
        'track_id'                 => $data['track_id'],
        'customer_id'              => $data['customer_id'] ?? null,
        'created_by_id'            => $data['created_by_id'] ?? null,
        'created_by_type'          => $data['created_by_type'],
        'purchase_verification_id' => $data['purchase_verification_id'] ?? null,
        'department_id'            => $data['department_id'],
        'category_id'              => $data['category_id'] ?? null,
        'assigned_agent_id'        => $data['assigned_agent_id'] ?? null,
        'subject'                  => $data['subject'],
        'status'                   => $data['status'],
        'state'                    => $data['state'],
        'priority'                 => $data['priority'],
        'source'                   => $data['source'],
        'last_message_id'          => $data['last_message_id'] ?? null,
        'last_reply_at'            => $data['last_reply_at'] ?? null,
        'first_response_at'        => $data['first_response_at'] ?? null,
        'resolved_at'              => $data['resolved_at'] ?? null,
        'closed_at'                => $data['closed_at'] ?? null,
        'reopened_at'              => $data['reopened_at'] ?? null,
        'is_public'                => $data['is_public'] ?? 0,
        'public_token'             => $data['public_token'] ?? null,
        'metadata'                 => $data['metadata'] ?? null,
        'created_at'               => $data['created_at'] ?? $this->now(),
        'updated_at'               => $data['updated_at'] ?? $this->now(),
      ],
      [
        '%s', // track_id
        '%d', // customer_id
        '%d', // created_by_id
        '%s', // created_by_type
        '%d', // purchase_verification_id
        '%d', // department_id
        '%d', // category_id
        '%d', // assigned_agent_id
        '%s', // subject
        '%s', // status
        '%s', // state
        '%s', // priority
        '%s', // source
        '%d', // last_message_id
        '%s', // last_reply_at
        '%s', // first_response_at
        '%s', // resolved_at
        '%s', // closed_at
        '%s', // reopened_at
        '%d', // is_public
        '%s', // public_token
        '%s', // metadata
        '%s', // created_at
        '%s', // updated_at
      ]
    );
  }


  /**
   * Find ticket by ID
   */
  public function find(int $id): ?Ticket {
    return $this->findById($id);
  }

  /**
   * Find ticket by track_id
   */
  public function findByTrackId(string $trackId): ?Ticket {
    return $this->first([
      'track_id' => $trackId,
    ]);
  }

  /**
   * Get tickets by customer
   */
  public function getByCustomer(int $customerId): array {
    return $this->findWhere([
      'customer_id' => $customerId,
    ], 'id', 'DESC');
  }

  public function countByCategory(int $categoryId): int {
    return (int) $this->db->get_var($this->db->prepare(
      "SELECT COUNT(*) FROM {$this->table()} WHERE category_id = %d",
      $categoryId,
    ));
  }

  /**
   * Query a paginated ticket workspace.
   *
   * @return array{items: Ticket[], total: int}
   */
  public function search(TicketQuery $query): array {
    $clauses = [];
    $values = [];

    foreach (['status', 'state', 'priority'] as $field) {
      $value = $query->{$field};

      if ($value !== null) {
        $clauses[] = "{$field} = %s";
        $values[] = $value;
      }
    }

    if ($query->customerId !== null) {
      $clauses[] = 'customer_id = %d';
      $values[] = $query->customerId;
    }

    if ($query->unassigned) {
      $clauses[] = 'assigned_agent_id IS NULL';
    } elseif ($query->assignedAgentId !== null) {
      $clauses[] = 'assigned_agent_id = %d';
      $values[] = $query->assignedAgentId;
    }

    if ($query->search !== null && $query->search !== '') {
      $like = '%' . $this->db->esc_like($query->search) . '%';
      $clauses[] = '(subject LIKE %s OR track_id LIKE %s)';
      $values[] = $like;
      $values[] = $like;
    }

    $where = $clauses ? 'WHERE ' . implode(' AND ', $clauses) : '';
    $countSql = "SELECT COUNT(*) FROM {$this->table()} {$where}";
    $total = (int) ($values
      ? $this->db->get_var($this->db->prepare($countSql, ...$values))
      : $this->db->get_var($countSql));
    $offset = ($query->page - 1) * $query->perPage;
    $orderBy = in_array($query->orderBy, ['created_at', 'updated_at', 'last_reply_at', 'priority'], true)
      ? $query->orderBy
      : 'updated_at';
    $direction = strtoupper($query->direction) === 'ASC' ? 'ASC' : 'DESC';
    $itemSql = "SELECT * FROM {$this->table()} {$where} ORDER BY {$orderBy} {$direction}, id DESC LIMIT %d OFFSET %d";
    $itemValues = [...$values, $query->perPage, $offset];
    $rows = $this->db->get_results($this->db->prepare($itemSql, ...$itemValues), ARRAY_A);

    return [
      'items' => array_map(fn(array $row): Ticket => $this->hydrate($row), $rows),
      'total' => $total,
    ];
  }

  /** @return array{items: TicketQueueItem[], total: int} */
  /** @param array<string, int> $slaTargets */
  public function searchQueue(
    TicketQuery $query,
    bool $slaEnabled = false,
    array $slaTargets = [],
    ?string $now = null,
  ): array {
    $ticketTable = $this->table();
    $messageTable = MessageSchema::tableName();
    $customerTable = CustomerSchema::tableName();
    $departmentTable = DepartmentSchema::tableName();
    $categoryTable = CategorySchema::tableName();
    $userTable = $this->db->users;
    $now = $now ?? $this->now();
    $slaTarget = "CASE t.priority
      WHEN 'urgent' THEN " . min(10080, max(15, (int) ($slaTargets['urgent'] ?? 60))) . "
      WHEN 'high' THEN " . min(10080, max(15, (int) ($slaTargets['high'] ?? 240))) . "
      WHEN 'medium' THEN " . min(10080, max(15, (int) ($slaTargets['medium'] ?? 480))) . "
      ELSE " . min(10080, max(15, (int) ($slaTargets['normal'] ?? 1440))) . ' END';
    $slaElapsed = "TIMESTAMPDIFF(MINUTE, t.created_at, COALESCE(t.first_response_at, '" . esc_sql($now) . "'))";
    $slaState = ! $slaEnabled ? "'disabled'" : "CASE
      WHEN t.first_response_at IS NOT NULL AND {$slaElapsed} <= {$slaTarget} THEN 'met'
      WHEN t.first_response_at IS NOT NULL OR {$slaElapsed} > {$slaTarget} THEN 'breached'
      WHEN ({$slaElapsed} * 4) >= ({$slaTarget} * 3) THEN 'due_soon'
      ELSE 'on_track' END";
    $clauses = [];
    $values = [];
    foreach (['status', 'state', 'priority'] as $field) {
      if ($query->{$field} !== null) { $clauses[] = "t.{$field} = %s"; $values[] = $query->{$field}; }
    }
    if ($query->departmentId !== null) { $clauses[] = 't.department_id = %d'; $values[] = $query->departmentId; }
    if ($query->uncategorized) { $clauses[] = 't.category_id IS NULL'; }
    elseif ($query->categoryId !== null) { $clauses[] = 't.category_id = %d'; $values[] = $query->categoryId; }
    if ($query->unassigned) { $clauses[] = 't.assigned_agent_id IS NULL'; }
    elseif ($query->assignedAgentId !== null) { $clauses[] = 't.assigned_agent_id = %d'; $values[] = $query->assignedAgentId; }
    if ($query->search) {
      $like = '%' . $this->db->esc_like($query->search) . '%';
      $clauses[] = '(t.subject LIKE %s OR t.track_id LIKE %s OR cu.display_name LIKE %s)';
      array_push($values, $like, $like, $like);
    }
    if ($query->slaState !== null) {
      $clauses[] = "({$slaState}) = %s";
      $values[] = $query->slaState;
    }
    $needExpression = "(lm.author_type IN ('customer','guest') AND t.status NOT IN ('resolved','closed') AND t.state = 'active')";
    if ($query->needsReply) { $clauses[] = $needExpression; }
    $where = $clauses ? 'WHERE ' . implode(' AND ', $clauses) : '';
    $aggregate = "(SELECT ticket_id, COUNT(*) reply_count, MAX(id) latest_reply_id FROM {$messageTable} WHERE type = 'reply' GROUP BY ticket_id) replies";
    $joins = "LEFT JOIN {$aggregate} ON replies.ticket_id=t.id LEFT JOIN {$messageTable} lm ON lm.id=replies.latest_reply_id LEFT JOIN {$customerTable} c ON c.id=t.customer_id LEFT JOIN {$userTable} cu ON cu.ID=c.user_id LEFT JOIN {$userTable} au ON au.ID=t.assigned_agent_id LEFT JOIN {$departmentTable} d ON d.id=t.department_id LEFT JOIN {$categoryTable} tc ON tc.id=t.category_id";
    $countSql = "SELECT COUNT(*) FROM {$ticketTable} t {$joins} {$where}";
    $total = (int) ($values ? $this->db->get_var($this->db->prepare($countSql, ...$values)) : $this->db->get_var($countSql));
    $order = strtoupper($query->direction) === 'ASC' ? 'ASC' : 'DESC';
    $orderBy = match ($query->orderBy) {
      'created_at' => 't.created_at', 'priority' => "CASE t.priority WHEN 'urgent' THEN 4 WHEN 'high' THEN 3 WHEN 'medium' THEN 2 ELSE 1 END",
      'need_reply' => $needExpression,
      'sla_due' => "CASE WHEN ({$slaState}) = 'breached' THEN 0 WHEN ({$slaState}) = 'due_soon' THEN 1 WHEN ({$slaState}) = 'on_track' THEN 2 ELSE 3 END, TIMESTAMPADD(MINUTE, {$slaTarget}, t.created_at)",
      default => 'COALESCE(t.last_reply_at,t.updated_at,t.created_at)',
    };
    $sql = "SELECT t.*, COALESCE(replies.reply_count,0) reply_count, {$needExpression} needs_reply, au.display_name agent_name, cu.display_name customer_name, c.avatar_url customer_avatar_url, d.name department_name, tc.name category_name,
      {$slaState} sla_state, {$slaTarget} sla_target_minutes,
      TIMESTAMPADD(MINUTE, {$slaTarget}, t.created_at) sla_due_at,
      CASE WHEN t.first_response_at IS NULL THEN ({$slaTarget} - {$slaElapsed}) ELSE NULL END sla_remaining_minutes
      FROM {$ticketTable} t {$joins} {$where} ORDER BY {$orderBy} {$order}, t.id DESC LIMIT %d OFFSET %d";
    $rows = $this->db->get_results($this->db->prepare($sql, ...[...$values, $query->perPage, ($query->page - 1) * $query->perPage]), ARRAY_A);
    return ['items' => array_map(static fn(array $row): TicketQueueItem => new TicketQueueItem($row), $rows), 'total' => $total];
  }

  /** @return array<string, mixed> */
  /** @param array<string, int> $slaTargets */
  public function metrics(TicketMetricQuery $query, array $slaTargets, string $now): array {
    $ticketTable = $this->table();
    $messageTable = MessageSchema::tableName();
    $departmentTable = DepartmentSchema::tableName();
    $clauses = ['t.created_at >= %s', 't.created_at <= %s', "t.state <> 'trash'"];
    $values = [$query->dateFrom . ' 00:00:00', $query->dateTo . ' 23:59:59'];

    if ($query->departmentId !== null) {
      $clauses[] = 't.department_id = %d';
      $values[] = $query->departmentId;
    }
    if ($query->uncategorized) {
      $clauses[] = 't.category_id IS NULL';
    } elseif ($query->categoryId !== null) {
      $clauses[] = 't.category_id = %d';
      $values[] = $query->categoryId;
    }
    if ($query->assignedAgentId !== null) {
      $clauses[] = 't.assigned_agent_id = %d';
      $values[] = $query->assignedAgentId;
    }
    if ($query->priority !== null) {
      $clauses[] = 't.priority = %s';
      $values[] = $query->priority;
    }

    $where = 'WHERE ' . implode(' AND ', $clauses);
    $replies = "(SELECT ticket_id,
      SUM(type = 'reply' AND author_type IN ('agent','manager')) AS responses,
      MAX(CASE WHEN type = 'reply' THEN id ELSE NULL END) AS latest_reply_id
      FROM {$messageTable} GROUP BY ticket_id) replies";
    $needReply = "(lm.author_type IN ('customer','guest') AND t.status NOT IN ('resolved','closed') AND t.state = 'active')";
    $target = "CASE t.priority
      WHEN 'urgent' THEN " . min(10080, max(15, (int) ($slaTargets['urgent'] ?? 60))) . "
      WHEN 'high' THEN " . min(10080, max(15, (int) ($slaTargets['high'] ?? 240))) . "
      WHEN 'medium' THEN " . min(10080, max(15, (int) ($slaTargets['medium'] ?? 480))) . "
      ELSE " . min(10080, max(15, (int) ($slaTargets['normal'] ?? 1440))) . ' END';
    $responseMinutes = 'TIMESTAMPDIFF(MINUTE, t.created_at, t.first_response_at)';
    $ageMinutes = "TIMESTAMPDIFF(MINUTE, t.created_at, '" . esc_sql($now) . "')";
    $joins = "LEFT JOIN {$replies} ON replies.ticket_id = t.id LEFT JOIN {$messageTable} lm ON lm.id = replies.latest_reply_id";
    $summary = $this->db->get_row($this->db->prepare(
      "SELECT COUNT(*) tickets,
        COALESCE(SUM(replies.responses), 0) responses,
        SUM({$needReply}) need_reply,
        SUM(t.status = 'resolved') resolved,
        SUM(t.status = 'closed') closed,
        ROUND(COALESCE(AVG(CASE WHEN t.first_response_at IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, t.created_at, t.first_response_at) END), 0), 1) average_first_response_minutes
       FROM {$ticketTable} t {$joins} {$where}",
      ...$values,
    ), ARRAY_A) ?: [];
    $sla = $this->db->get_row($this->db->prepare(
      "SELECT
        SUM(t.first_response_at IS NOT NULL AND {$responseMinutes} <= {$target}) within_target,
        SUM((t.first_response_at IS NOT NULL AND {$responseMinutes} > {$target}) OR (t.first_response_at IS NULL AND {$ageMinutes} > {$target})) breached,
        SUM(t.first_response_at IS NULL AND {$ageMinutes} <= {$target}) awaiting_within_target,
        SUM(t.first_response_at IS NOT NULL AND {$responseMinutes} < 60) under_1h,
        SUM(t.first_response_at IS NOT NULL AND {$responseMinutes} >= 60 AND {$responseMinutes} < 240) from_1h_to_4h,
        SUM(t.first_response_at IS NOT NULL AND {$responseMinutes} >= 240 AND {$responseMinutes} < 1440) from_4h_to_24h,
        SUM(t.first_response_at IS NOT NULL AND {$responseMinutes} >= 1440) over_24h,
        SUM(t.first_response_at IS NULL) no_response
       FROM {$ticketTable} t {$where}",
      ...$values,
    ), ARRAY_A) ?: [];

    return [
      'summary' => [
        'tickets' => (int) ($summary['tickets'] ?? 0),
        'responses' => (int) ($summary['responses'] ?? 0),
        'need_reply' => (int) ($summary['need_reply'] ?? 0),
        'resolved' => (int) ($summary['resolved'] ?? 0),
        'closed' => (int) ($summary['closed'] ?? 0),
        'average_first_response_minutes' => (float) ($summary['average_first_response_minutes'] ?? 0),
      ],
      'sla' => [
        'within_target' => (int) ($sla['within_target'] ?? 0),
        'breached' => (int) ($sla['breached'] ?? 0),
        'awaiting_within_target' => (int) ($sla['awaiting_within_target'] ?? 0),
      ],
      'response_bands' => [
        'under_1h' => (int) ($sla['under_1h'] ?? 0),
        'from_1h_to_4h' => (int) ($sla['from_1h_to_4h'] ?? 0),
        'from_4h_to_24h' => (int) ($sla['from_4h_to_24h'] ?? 0),
        'over_24h' => (int) ($sla['over_24h'] ?? 0),
        'no_response' => (int) ($sla['no_response'] ?? 0),
      ],
      'daily' => $this->ticketMetricGroups(
        "DATE(t.created_at) AS group_key",
        $where,
        $values,
        $joins,
        $needReply,
        'group_key ASC',
        'date',
      ),
      'departments' => $this->ticketMetricGroups(
        "COALESCE(d.name, 'Unknown') AS group_key",
        $where,
        $values,
        $joins . " LEFT JOIN {$departmentTable} d ON d.id = t.department_id",
        $needReply,
        'tickets DESC, group_key ASC',
        'department',
      ),
      'categories' => $this->ticketMetricGroups(
        "COALESCE(tc.name, 'Uncategorized') AS group_key",
        $where,
        $values,
        $joins . " LEFT JOIN " . CategorySchema::tableName() . " tc ON tc.id = t.category_id",
        $needReply,
        'tickets DESC, group_key ASC',
        'category',
      ),
      'agents' => $this->ticketMetricGroups(
        "COALESCE(au.display_name, 'Unassigned') AS group_key",
        $where,
        $values,
        $joins . " LEFT JOIN {$this->db->users} au ON au.ID = t.assigned_agent_id",
        $needReply,
        'tickets DESC, group_key ASC',
        'agent',
      ),
    ];
  }

  /**
   * Get tickets linked to a purchase verification.
   *
   * @return Ticket[]
   */
  public function findByVerification(
    int $verificationId,
  ): array {
    /** @var Ticket[] */
    return $this->findWhere([
      'purchase_verification_id' => $verificationId,
    ], 'id', 'DESC');
  }

  /**
   * Update ticket
   */
  public function update(int $id, array $data): bool {
    $data['updated_at'] = $this->now();

    return $this->updateById($id, $data);
  }

  /**
   * Delete ticket (soft-delete can be added later)
   */
  public function delete(int $id): bool {
    return $this->deleteById($id);
  }

  /**
   * Hydrate DB row → Ticket Entity
   */
  protected function hydrate(array $row): object {
    return new Ticket(
      id: (int) $row['id'],
      trackId: (string) $row['track_id'],

      customerId: isset($row['customer_id'])
        ? (int) $row['customer_id']
        : null,

      createdById: isset($row['created_by_id'])
        ? (int) $row['created_by_id']
        : null,

      createdByType: AuthorType::from($row['created_by_type']),

      purchaseVerificationId: isset($row['purchase_verification_id'])
        ? (int) $row['purchase_verification_id']
        : null,

      departmentId: (int) $row['department_id'],
      categoryId: isset($row['category_id']) ? (int) $row['category_id'] : null,

      assignedAgentId: isset($row['assigned_agent_id'])
        ? (int) $row['assigned_agent_id']
        : null,

      subject: (string) $row['subject'],

      status: TicketStatus::from($row['status']),
      state: TicketState::from($row['state']),
      priority: TicketPriority::from($row['priority']),
      source: SourceType::from($row['source']),

      lastMessageId: isset($row['last_message_id'])
        ? (int) $row['last_message_id']
        : null,

      lastReplyAt: $row['last_reply_at'] ?? null,
      firstResponseAt: $row['first_response_at'] ?? null,
      resolvedAt: $row['resolved_at'] ?? null,
      closedAt: $row['closed_at'] ?? null,
      reopenedAt: $row['reopened_at'] ?? null,

      isPublic: (bool) $row['is_public'],
      publicToken: $row['public_token'] ?? null,

      metadata: $row['metadata'] ?? null,

      createdAt: $row['created_at'],
      updatedAt: $row['updated_at'] ?? null,
    );
  }

  /**
   * @param array<int, int|string> $values
   * @return array<int, array<string, int|string>>
   */
  private function ticketMetricGroups(
    string $selection,
    string $where,
    array $values,
    string $joins,
    string $needReply,
    string $order,
    string $label,
  ): array {
    $rows = $this->db->get_results($this->db->prepare(
      "SELECT {$selection}, COUNT(*) tickets,
        COALESCE(SUM(replies.responses), 0) responses,
        SUM({$needReply}) need_reply,
        SUM(t.status IN ('resolved','closed')) closed
       FROM {$this->table()} t {$joins} {$where}
       GROUP BY group_key ORDER BY {$order}",
      ...$values,
    ), ARRAY_A);

    return array_map(static fn(array $row): array => [
      $label => (string) $row['group_key'],
      'tickets' => (int) $row['tickets'],
      'responses' => (int) $row['responses'],
      'need_reply' => (int) $row['need_reply'],
      'closed' => (int) $row['closed'],
    ], $rows);
  }
}
