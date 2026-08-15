<?php

declare(strict_types=1);

namespace SupportBay\Modules\Notifications\Services;

use DateInterval;
use DatePeriod;
use DateTimeImmutable;
use InvalidArgumentException;
use SupportBay\Modules\Notifications\Data\NotificationMetricQuery;
use SupportBay\Modules\Notifications\Repositories\NotificationLogRepository;

final class NotificationMetricService {
  public function __construct(
    private readonly NotificationLogRepository $logs,
  ) {
  }

  /** @return array<string, mixed> */
  public function report(NotificationMetricQuery $query): array {
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
      throw new InvalidArgumentException('Notification reports are limited to 367 days.');
    }

    $metrics = $this->logs->metrics($query);
    $daily = [];

    foreach ($metrics['daily'] as $row) {
      $daily[(string) $row['date']] = $row;
    }

    $filledDaily = [];
    $period = new DatePeriod($from, new DateInterval('P1D'), $to->modify('+1 day'));

    foreach ($period as $date) {
      $key = $date->format('Y-m-d');
      $filledDaily[] = $daily[$key] ?? [
        'date' => $key,
        'total' => 0,
        'successful' => 0,
        'failed' => 0,
      ];
    }

    $total = (int) $metrics['summary']['total'];
    $successful = (int) $metrics['summary']['successful'];
    $failed = (int) $metrics['summary']['failed'];

    return [
      'range' => ['from' => $query->dateFrom, 'to' => $query->dateTo],
      'filters' => ['channel' => $query->channel, 'event' => $query->event],
      'summary' => [
        ...$metrics['summary'],
        'success_rate' => $total > 0 ? round(($successful / $total) * 100, 1) : 0.0,
        'failure_rate' => $total > 0 ? round(($failed / $total) * 100, 1) : 0.0,
      ],
      'daily' => $filledDaily,
      'events' => $metrics['events'],
      'channels' => $metrics['channels'],
    ];
  }
}
