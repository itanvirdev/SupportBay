<?php

declare(strict_types=1);

namespace SupportBay\Modules\Tickets\Services;

use RuntimeException;
use SupportBay\Common\Enums\AuthorType;
use SupportBay\Common\Enums\SourceType;
use SupportBay\Modules\Tickets\Entities\Ticket;
use SupportBay\Modules\Tickets\Enums\TicketPriority;
use SupportBay\Modules\Tickets\Enums\TicketState;
use SupportBay\Modules\Tickets\Enums\TicketStatus;
use SupportBay\Modules\Tickets\Repositories\TicketRepository;
use SupportBay\Modules\Verifications\Services\VerificationService;

final class TicketService {
  public function __construct(
    private readonly TicketRepository $repository,
    private readonly VerificationService $verifications,
  ) {
  }

  /**
   * Create a ticket
   */
  public function create(array $data): int {
    $this->validateVerification($data);

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
   * Find tickets belonging to a customer.
   *
   * @return Ticket[]
   */
  public function findByCustomer(int $customerId): array {
    return $this->repository->getByCustomer($customerId);
  }

  /**
   * Find tickets related through the same purchase verification.
   *
   * @return Ticket[]
   */
  public function findByVerification(
    int $verificationId,
  ): array {
    $this->verifications->findOrFail($verificationId);

    return $this->repository->findByVerification(
      $verificationId
    );
  }

  /**
   * Delete a ticket.
   */
  public function delete(int $id): bool {
    return $this->repository->delete($id);
  }

  /**
   * Validate an optional purchase verification relationship.
   *
   * @param array<string, mixed> $data
   */
  private function validateVerification(array $data): void {
    if (empty($data['purchase_verification_id'])) {
      return;
    }

    $verification = $this->verifications->findOrFail(
      (int) $data['purchase_verification_id']
    );

    if (! $verification->isValid()) {
      throw new RuntimeException(
        'Tickets can only be linked to a valid purchase verification.'
      );
    }

    $customerId = isset($data['customer_id'])
      ? (int) $data['customer_id']
      : null;

    if (
      $customerId === null ||
      $verification->customerId() !== $customerId
    ) {
      throw new RuntimeException(
        'Purchase verification does not belong to the ticket customer.'
      );
    }
  }

  /**
   * Generate trackId
   */
  private function generateTrackId(): string {
    return strtoupper(bin2hex(random_bytes(4)));
  }
}
