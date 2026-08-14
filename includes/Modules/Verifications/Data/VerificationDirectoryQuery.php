<?php

declare(strict_types=1);

namespace SupportBay\Modules\Verifications\Data;

final class VerificationDirectoryQuery {
  public function __construct(
    public readonly int $page = 1,
    public readonly int $perPage = 20,
    public readonly ?string $search = null,
    public readonly ?string $provider = null,
    public readonly ?string $status = null,
    public readonly string $orderBy = 'updated_at',
    public readonly string $direction = 'desc',
  ) {
  }
}
