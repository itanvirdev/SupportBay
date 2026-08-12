<?php

declare(strict_types=1);

namespace SupportBay\Modules\Admin\Data;

final class CustomerDirectoryQuery {
  public function __construct(
    public readonly int $page = 1,
    public readonly int $perPage = 20,
    public readonly ?string $search = null,
    public readonly ?string $state = null,
    public readonly ?string $source = null,
    public readonly string $orderBy = 'last_activity',
    public readonly string $direction = 'desc',
  ) {
  }
}
