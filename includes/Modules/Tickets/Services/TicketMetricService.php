<?php

declare(strict_types=1);

namespace SupportBay\Modules\Tickets\Services;

use DateInterval;
use DatePeriod;
use DateTimeImmutable;
use InvalidArgumentException;
use SupportBay\Modules\Tickets\Data\TicketMetricQuery;
use SupportBay\Modules\Tickets\Enums\TicketPriority;
use SupportBay\Modules\Tickets\Repositories\TicketRepository;
use SupportBay\Common\Utilities\CsvExporter;

final class TicketMetricService {
  public function __construct(
    private readonly TicketRepository $tickets,
    private readonly CsvExporter $csv,
    private readonly TicketSlaPolicyService $sla,
  ) {
  }

  public function export(TicketMetricQuery $query): string {
    $report = $this->report($query);
    $summary = $report['summary'];

    return $this->csv->generate([
      [
        'name' => 'Ticket performance summary',
        'headers' => ['Date from', 'Date to', 'Tickets', 'Responses', 'Need reply', 'Resolved', 'Closed', 'Average first response (minutes)', 'SLA within target', 'SLA breached', 'SLA awaiting'],
        'rows' => [[
          $report['range']['from'], $report['range']['to'], $summary['tickets'],
          $summary['responses'], $summary['need_reply'], $summary['resolved'],
          $summary['closed'], $summary['average_first_response_minutes'],
          $summary['sla']['within_target'], $summary['sla']['breached'], $summary['sla']['awaiting_within_target'],
        ]],
      ],
      [
        'name' => 'First-response bands',
        'headers' => ['Under 1 hour', '1 to 4 hours', '4 to 24 hours', '24 hours or more', 'No response'],
        'rows' => [[
          $summary['response_bands']['under_1h'], $summary['response_bands']['from_1h_to_4h'],
          $summary['response_bands']['from_4h_to_24h'], $summary['response_bands']['over_24h'],
          $summary['response_bands']['no_response'],
        ]],
      ],
      [
        'name' => 'Daily activity',
        'headers' => ['Date', 'Tickets', 'Responses', 'Need reply', 'Resolved or closed'],
        'rows' => array_map(static fn(array $row): array => [
          $row['date'], $row['tickets'], $row['responses'], $row['need_reply'], $row['closed'],
        ], $report['daily']),
      ],
      [
        'name' => 'Department workload',
        'headers' => ['Department', 'Tickets', 'Responses', 'Need reply', 'Resolved or closed'],
        'rows' => array_map(static fn(array $row): array => [
          $row['department'], $row['tickets'], $row['responses'], $row['need_reply'], $row['closed'],
        ], $report['departments']),
      ],
      [
        'name' => 'Agent workload',
        'headers' => ['Agent', 'Tickets', 'Responses', 'Need reply', 'Resolved or closed'],
        'rows' => array_map(static fn(array $row): array => [
          $row['agent'], $row['tickets'], $row['responses'], $row['need_reply'], $row['closed'],
        ], $report['agents']),
      ],
      [
        'name' => 'Category workload',
        'headers' => ['Category', 'Tickets', 'Responses', 'Need reply', 'Resolved or closed'],
        'rows' => array_map(static fn(array $row): array => [
          $row['category'], $row['tickets'], $row['responses'], $row['need_reply'], $row['closed'],
        ], $report['categories']),
      ],
    ]);
  }

  /** @return array<string, mixed> */
  public function report(TicketMetricQuery $query): array {
    $from = DateTimeImmutable::createFromFormat('!Y-m-d', $query->dateFrom);
    $to = DateTimeImmutable::createFromFormat('!Y-m-d', $query->dateTo);

    if (! $from || $from->format('Y-m-d') !== $query->dateFrom
      || ! $to || $to->format('Y-m-d') !== $query->dateTo) {
      throw new InvalidArgumentException('Report dates must use the Y-m-d format.');
    }
    if ($from > $to) {
      throw new InvalidArgumentException('Report start date must not be after the end date.');
    }
    if ((int) $from->diff($to)->days > 366) {
      throw new InvalidArgumentException('Ticket reports are limited to 367 days.');
    }
    if ($query->priority !== null && TicketPriority::tryFrom($query->priority) === null) {
      throw new InvalidArgumentException('Unknown ticket priority.');
    }

    $policy = $this->sla->get();
    $metrics = $this->tickets->metrics(
      $query,
      $policy->firstResponseMinutes(),
      current_time('mysql'),
    );
    $daily = [];
    foreach ($metrics['daily'] as $row) {
      $daily[(string) $row['date']] = $row;
    }
    $filled = [];
    foreach (new DatePeriod($from, new DateInterval('P1D'), $to->modify('+1 day')) as $date) {
      $key = $date->format('Y-m-d');
      $filled[] = $daily[$key] ?? [
        'date' => $key,
        'tickets' => 0,
        'responses' => 0,
        'need_reply' => 0,
        'closed' => 0,
      ];
    }

    return [
      'range' => ['from' => $query->dateFrom, 'to' => $query->dateTo],
      'filters' => [
        'department_id' => $query->departmentId,
        'category_id' => $query->categoryId,
        'uncategorized' => $query->uncategorized,
        'assigned_agent_id' => $query->assignedAgentId,
        'priority' => $query->priority,
      ],
      'summary' => [
        ...$metrics['summary'],
        'sla' => [
          'enabled' => $policy->enabled(),
          'targets' => $policy->firstResponseMinutes(),
          'within_target' => $policy->enabled() ? $metrics['sla']['within_target'] : 0,
          'breached' => $policy->enabled() ? $metrics['sla']['breached'] : 0,
          'awaiting_within_target' => $policy->enabled() ? $metrics['sla']['awaiting_within_target'] : 0,
        ],
        'response_bands' => $metrics['response_bands'],
      ],
      'daily' => $filled,
      'departments' => $metrics['departments'],
      'categories' => $metrics['categories'],
      'agents' => $metrics['agents'],
    ];
  }
}
