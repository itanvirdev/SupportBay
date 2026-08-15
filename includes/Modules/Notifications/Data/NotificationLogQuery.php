<?php

declare(strict_types=1);

namespace SupportBay\Modules\Notifications\Data;

final readonly class NotificationLogQuery {
  public function __construct(
    public int $page = 1,
    public int $perPage = 20,
    public ?string $search = null,
    public ?string $channel = null,
    public ?string $event = null,
    public ?string $status = null,
    public string $orderBy = 'created_at',
    public string $direction = 'desc',
  ) {
  }
}
