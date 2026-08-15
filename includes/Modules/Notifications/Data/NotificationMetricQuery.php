<?php

declare(strict_types=1);

namespace SupportBay\Modules\Notifications\Data;

final readonly class NotificationMetricQuery {
  public function __construct(
    public string $dateFrom,
    public string $dateTo,
    public ?string $channel = null,
    public ?string $event = null,
  ) {
  }
}
