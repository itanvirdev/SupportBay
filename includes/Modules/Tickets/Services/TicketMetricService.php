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
  ) {
  }

  public function export(TicketMetricQuery $query): string {
    $report = $this->report($query);
    $summary = $report['summary'];

    return $this->csv->generate([
      [
        'name' => 'Ticket performance summary',
        'headers' => ['Date from', 'Date to', 'Tickets', 'Responses', 'Need reply', 'Resolved', 'Closed'],
        'rows' => [[
          $report['range']['from'], $report['range']['to'], $summary['tickets'],
          $summary['responses'], $summary['need_reply'], $summary['resolved'],
          $summary['closed'],
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
      [
        'name' => 'Tag workload',
        'headers' => ['Tag', 'Tickets', 'Responses', 'Need reply', 'Resolved or closed'],
        'rows' => array_map(static fn(array $row): array => [
          $row['tag'], $row['tickets'], $row['responses'], $row['need_reply'], $row['closed'],
        ], $report['tags']),
      ],
      [
        'name' => 'Custom field workload',
        'headers' => ['Value', 'Tickets', 'Responses', 'Need reply', 'Resolved or closed'],
        'rows' => array_map(static fn(array $row): array => [
          $row['value'], $row['tickets'], $row['responses'], $row['need_reply'], $row['closed'],
        ], $report['custom_fields']),
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
    if ($query->customFieldValue !== null && $query->customFieldId === null) {
      throw new InvalidArgumentException('A custom field is required when filtering by value.');
    }

    $metrics = $this->tickets->metrics($query);
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
        'tag_id' => $query->tagId,
        'custom_field_id' => $query->customFieldId,
        'custom_field_value' => $query->customFieldValue,
        'assigned_agent_id' => $query->assignedAgentId,
        'priority' => $query->priority,
      ],
      'summary' => $metrics['summary'],
      'daily' => $filled,
      'departments' => $metrics['departments'],
      'categories' => $metrics['categories'],
      'tags' => $metrics['tags'],
      'custom_fields' => $metrics['custom_fields'],
      'agents' => $metrics['agents'],
    ];
  }
}
