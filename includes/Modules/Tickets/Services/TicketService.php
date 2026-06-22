<?php

declare(strict_types=1);

namespace SupportBay\Modules\Tickets\Services;

use SupportBay\Modules\Tickets\Repositories\TicketRepository;
use SupportBay\Common\Enums\AuthorType;
use SupportBay\Common\Enums\SourceType;

final class TicketService {
  public function __construct(
    private TicketRepository $repository
  ) {
  }

  /**
   * Create a ticket
   */
  public function create(array $data): int {
    $data['track_id'] = $data['track_id'] ?? $this->generateTrackId();

    $data['source'] = $data['source'] ?? SourceType::default()->value;
    $data['created_by_type'] = $data['created_by_type'] ?? AuthorType::default()->value;

    return $this->repository->create($data);
  }

  private function generateTrackId(): string {
    return strtoupper(bin2hex(random_bytes(4)));
  }
}
