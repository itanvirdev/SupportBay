<?php

declare(strict_types=1);

namespace SupportBay\Modules\Tickets\Services;

use SupportBay\Modules\Tickets\Repositories\TicketRepository;

final class TicketService {
  public function __construct(
    private TicketRepository $repository
  ) {
  }

  public function create(array $data) {
    $data['track_id'] = $this->generateTrackId();

    return $this->repository->create($data);
  }

  private function generateTrackId(): string {
    return strtoupper(bin2hex(random_bytes(4)));
  }
}
