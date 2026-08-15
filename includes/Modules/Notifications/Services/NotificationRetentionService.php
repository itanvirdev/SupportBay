<?php

declare(strict_types=1);

namespace SupportBay\Modules\Notifications\Services;

use DateTimeImmutable;
use InvalidArgumentException;
use SupportBay\Modules\Notifications\Entities\NotificationRetention;
use SupportBay\Modules\Notifications\Repositories\NotificationLogRepository;
use SupportBay\Modules\Notifications\Repositories\NotificationRetentionRepository;

final class NotificationRetentionService {
  public function __construct(
    private readonly NotificationRetentionRepository $retention,
    private readonly NotificationLogRepository $logs,
  ) {
  }

  public function get(): NotificationRetention {
    return $this->retention->get();
  }

  /** @param array<string, mixed> $data */
  public function update(array $data): NotificationRetention {
    $existing = $this->get();
    $enabled = array_key_exists('enabled', $data)
      ? $this->boolean($data['enabled'])
      : $existing->enabled();
    $days = array_key_exists('retention_days', $data)
      ? (int) $data['retention_days']
      : $existing->retentionDays();
    $batch = array_key_exists('batch_size', $data)
      ? (int) $data['batch_size']
      : $existing->batchSize();

    if ($days < 7 || $days > 3650) {
      throw new InvalidArgumentException('Retention days must be between 7 and 3650.');
    }

    if ($batch < 50 || $batch > 1000) {
      throw new InvalidArgumentException('Cleanup batch size must be between 50 and 1000.');
    }

    $retention = new NotificationRetention($enabled, $days, $batch);
    $this->retention->save($retention);

    return $retention;
  }

  /** @return array{deleted: int, cutoff: string} */
  public function cleanup(): array {
    $retention = $this->get();
    $cutoff = (new DateTimeImmutable(current_time('mysql')))
      ->modify(sprintf('-%d days', $retention->retentionDays()))
      ->format('Y-m-d H:i:s');

    return [
      'deleted' => $retention->enabled()
        ? $this->logs->deleteExpiredTerminal($cutoff, $retention->batchSize())
        : 0,
      'cutoff' => $cutoff,
    ];
  }

  private function boolean(mixed $value): bool {
    if (is_bool($value)) {
      return $value;
    }

    if (in_array($value, [0, 1, '0', '1'], true)) {
      return (bool) $value;
    }

    throw new InvalidArgumentException('Retention enabled must be boolean.');
  }
}
