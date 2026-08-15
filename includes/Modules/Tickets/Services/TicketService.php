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
use SupportBay\Modules\Tickets\Enums\TicketBulkAction;
use SupportBay\Modules\Tickets\Repositories\TicketRepository;
use SupportBay\Modules\Tickets\Events\TicketClosed;
use SupportBay\Modules\Tickets\Events\TicketCreated;
use SupportBay\Modules\Tickets\Events\TicketReopened;
use SupportBay\Modules\Tickets\Events\TicketChanged;
use SupportBay\Modules\Tickets\Events\TicketAssignmentChanged;
use SupportBay\Modules\Tickets\Events\TicketResolved;
use SupportBay\Modules\Tickets\Data\TicketQuery;
use SupportBay\Modules\Verifications\Services\VerificationService;
use SupportBay\Core\Events\EventDispatcher;

final class TicketService {
  public function __construct(
    private readonly TicketRepository $repository,
    private readonly VerificationService $verifications,
    private readonly EventDispatcher $events,
    private readonly TicketSlaPolicyService $sla,
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

    $ticketId = $this->repository->create($data);
    $ticket = $this->find($ticketId);

    if (! $ticket) {
      throw new RuntimeException('Unable to load created ticket.');
    }

    $this->events->dispatch(new TicketCreated($ticket));

    return $ticketId;
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

  /** @return array{items: Ticket[], total: int} */
  public function search(TicketQuery $query): array {
    return $this->repository->search($query);
  }

  /** @return array{items: \SupportBay\Modules\Tickets\Data\TicketQueueItem[], total: int} */
  public function searchQueue(TicketQuery $query): array {
    $policy = $this->sla->get();
    return $this->repository->searchQueue(
      $query,
      $policy->enabled(),
      $policy->firstResponseMinutes(),
      current_time('mysql'),
    );
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
   * Close a ticket.
   */
  public function close(int $id): Ticket {
    $ticket = $this->findOrFail($id);

    if ($ticket->isClosed()) {
      throw new RuntimeException('Ticket is already closed.');
    }

    $this->repository->update($id, [
      'status'    => TicketStatus::CLOSED->value,
      'closed_at' => current_time('mysql'),
    ]);

    $closed = $this->findOrFail($id);
    $this->events->dispatch(new TicketClosed($closed));

    return $closed;
  }

  /** Resolve a ticket that is still accepting replies. */
  public function resolve(int $id, int $actorId): Ticket {
    $ticket = $this->findOrFail($id);

    if (! $ticket->status()->canReceiveReplies()) {
      throw new RuntimeException('Only active tickets can be resolved.');
    }

    $this->repository->update($id, [
      'status' => TicketStatus::RESOLVED->value,
      'resolved_at' => current_time('mysql'),
    ]);

    $resolved = $this->findOrFail($id);
    $this->events->dispatch(new TicketResolved($resolved, $actorId));

    return $resolved;
  }

  /**
   * Reopen a closed ticket.
   */
  public function reopen(int $id): Ticket {
    $ticket = $this->findOrFail($id);

    if (! $ticket->canBeReopened()) {
      throw new RuntimeException('Only resolved or closed tickets can be reopened.');
    }

    $this->repository->update($id, [
      'status'      => TicketStatus::OPEN->value,
      'closed_at'   => null,
      'resolved_at' => null,
      'reopened_at' => current_time('mysql'),
    ]);

    $reopened = $this->findOrFail($id);
    $this->events->dispatch(new TicketReopened($reopened));

    return $reopened;
  }

  public function changeAssignment(int $id, ?int $agentId, int $actorId): Ticket {
    $existing = $this->findOrFail($id);

    if ($existing->assignedAgentId() === $agentId) {
      return $existing;
    }

    $this->repository->update($id, ['assigned_agent_id' => $agentId]);
    $ticket = $this->findOrFail($id);
    $this->events->dispatch(new TicketChanged($ticket, 'assignment', $actorId));
    $this->events->dispatch(new TicketAssignmentChanged(
      ticket: $ticket,
      previousAgentId: $existing->assignedAgentId(),
      actorId: $actorId,
    ));

    return $ticket;
  }

  public function changeDepartment(int $id, int $departmentId, int $actorId): Ticket {
    return $this->change($id, ['department_id' => $departmentId], 'department', $actorId);
  }

  public function changePriority(int $id, TicketPriority $priority, int $actorId): Ticket {
    return $this->change($id, ['priority' => $priority->value], 'priority', $actorId);
  }

  public function changeState(int $id, TicketState $state, int $actorId): Ticket {
    return $this->change($id, ['state' => $state->value], 'state', $actorId);
  }

  /**
   * Apply one supported mutation to a collection of tickets.
   *
   * Each successful ticket still emits its normal TicketChanged event. A missing
   * or otherwise invalid ticket is reported without discarding valid updates.
   *
   * @param int[] $ticketIds
   * @return array{updated: Ticket[], failed: array<int, string>}
   */
  public function bulkChange(
    array $ticketIds,
    TicketBulkAction $action,
    mixed $value,
    int $actorId,
  ): array {
    $updated = [];
    $failed = [];

    foreach (array_values(array_unique(array_map('absint', $ticketIds))) as $ticketId) {
      if ($ticketId === 0) {
        continue;
      }

      try {
        $updated[] = match ($action) {
          TicketBulkAction::ASSIGNMENT => $this->changeAssignment($ticketId, absint($value) ?: null, $actorId),
          TicketBulkAction::DEPARTMENT => $this->changeDepartment($ticketId, absint($value), $actorId),
          TicketBulkAction::PRIORITY => $this->changePriority($ticketId, TicketPriority::from(sanitize_key((string) $value)), $actorId),
          TicketBulkAction::STATE => $this->changeState($ticketId, TicketState::from(sanitize_key((string) $value)), $actorId),
        };
      } catch (\ValueError|RuntimeException $exception) {
        $failed[$ticketId] = $exception->getMessage();
      }
    }

    return ['updated' => $updated, 'failed' => $failed];
  }

  /** @param array<string, mixed> $updates */
  private function change(int $id, array $updates, string $change, int $actorId): Ticket {
    $this->findOrFail($id);
    $this->repository->update($id, $updates);
    $ticket = $this->findOrFail($id);
    $this->events->dispatch(new TicketChanged($ticket, $change, $actorId));
    return $ticket;
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

    if (! $verification->hasActiveSupport()) {
      throw new RuntimeException(
        'Tickets require a verified purchase with active support.'
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
   * Find a ticket or fail the workflow.
   */
  private function findOrFail(int $id): Ticket {
    $ticket = $this->repository->find($id);

    if (! $ticket) {
      throw new RuntimeException('Ticket was not found.');
    }

    return $ticket;
  }

  /**
   * Generate trackId
   */
  private function generateTrackId(): string {
    return strtoupper(bin2hex(random_bytes(4)));
  }
}
