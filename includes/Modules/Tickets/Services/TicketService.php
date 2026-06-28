<?php

declare(strict_types=1);

namespace SupportBay\Modules\Tickets\Services;

use SupportBay\Common\Enums\AuthorType;
use SupportBay\Common\Enums\SourceType;
use SupportBay\Modules\Tickets\Entities\Ticket;
use SupportBay\Modules\Tickets\Enums\TicketPriority;
use SupportBay\Modules\Tickets\Enums\TicketState;
use SupportBay\Modules\Tickets\Enums\TicketStatus;
use SupportBay\Modules\Tickets\Repositories\TicketRepository;

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

    $data['status']          = $data['status'] ?? TicketStatus::default()->value;
    $data['state']           = $data['state'] ?? TicketState::default()->value;
    $data['priority']        = $data['priority'] ?? TicketPriority::default()->value;
    $data['source']          = $data['source'] ?? SourceType::default()->value;
    $data['created_by_type'] = $data['created_by_type'] ?? AuthorType::default()->value;

    return $this->repository->create($data);
  }

  /**
   * Find ticket.
   */
  public function find(int $id): ?Ticket {
    return $this->repository->find($id);
  }

  /**
   * Find number.
   */
  public function findByNumber(string $number): ?Ticket {
    return $this->repository->findByNumber($number);
  }

  /**
   * @return Ticket[]
   */
  public function all(): array {
    return $this->repository->all();
  }

  /**
   * Generate trackId
   */
  private function generateTrackId(): string {
    return strtoupper(bin2hex(random_bytes(4)));
  }
}
