<?php

declare(strict_types=1);

namespace SupportBay\Modules\Admin\Services;

use SupportBay\Modules\Admin\Data\CustomerDirectoryQuery;
use SupportBay\Modules\Admin\Repositories\CustomerDirectoryRepository;

final class CustomerDirectoryService {
  public function __construct(private readonly CustomerDirectoryRepository $customers) {}

  /** @return array{items: \SupportBay\Modules\Admin\Data\CustomerDirectoryItem[], total: int} */
  public function search(CustomerDirectoryQuery $query): array {
    return $this->customers->search($query);
  }
}
