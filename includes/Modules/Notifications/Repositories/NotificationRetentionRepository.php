<?php

declare(strict_types=1);

namespace SupportBay\Modules\Notifications\Repositories;

use SupportBay\Modules\Notifications\Entities\NotificationRetention;

final class NotificationRetentionRepository {
  private const OPTION = 'sbay_notification_retention';

  public function get(): NotificationRetention {
    $stored = get_option(self::OPTION, []);
    $stored = is_array($stored) ? $stored : [];

    return new NotificationRetention(
      enabled: array_key_exists('enabled', $stored) ? (bool) $stored['enabled'] : true,
      retentionDays: isset($stored['retention_days']) ? (int) $stored['retention_days'] : 90,
      batchSize: isset($stored['batch_size']) ? (int) $stored['batch_size'] : 500,
    );
  }

  public function save(NotificationRetention $retention): void {
    update_option(self::OPTION, $retention->toArray(), false);
  }
}
