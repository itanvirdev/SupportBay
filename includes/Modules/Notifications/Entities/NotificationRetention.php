<?php

declare(strict_types=1);

namespace SupportBay\Modules\Notifications\Entities;

use SupportBay\Core\Entities\Entity;

final class NotificationRetention extends Entity {
  public function __construct(
    private bool $enabled,
    private int $retentionDays,
    private int $batchSize,
  ) {
  }

  public function toArray(): array {
    return [
      'enabled' => $this->enabled,
      'retention_days' => $this->retentionDays,
      'batch_size' => $this->batchSize,
    ];
  }

  public function enabled(): bool { return $this->enabled; }

  public function retentionDays(): int { return $this->retentionDays; }

  public function batchSize(): int { return $this->batchSize; }
}
