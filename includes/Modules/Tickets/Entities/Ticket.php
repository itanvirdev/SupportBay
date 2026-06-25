<?php

declare(strict_types=1);

namespace SupportBay\Modules\Tickets\Entities;

use SupportBay\Common\Enums\AuthorType;
use SupportBay\Common\Enums\SourceType;
use SupportBay\Core\Entities\Entity;
use SupportBay\Modules\Tickets\Enums\TicketSource;
use SupportBay\Modules\Tickets\Enums\TicketPriority;
use SupportBay\Modules\Tickets\Enums\TicketState;
use SupportBay\Modules\Tickets\Enums\TicketStatus;

final class Ticket extends Entity {
  public function __construct(
    private int $id,
    private string $trackId,

    private ?int $customerId,

    private ?int $createdById,
    private AuthorType $createdByType,

    private ?int $purchaseVerificationId,

    private int $departmentId,

    private ?int $assignedAgentId,

    private string $subject,

    private TicketStatus $status,
    private TicketState $state,
    private TicketPriority $priority,
    private SourceType $source,

    private ?int $lastMessageId,

    private ?string $lastReplyAt,
    private ?string $firstResponseAt,
    private ?string $resolvedAt,
    private ?string $closedAt,
    private ?string $reopenedAt,

    private bool $isPublic,
    private ?string $publicToken,

    private ?string $metadata,

    private string $createdAt,
    private ?string $updatedAt,
  ) {
  }

  /**
   * Convert entity to array.
   */
  public function toArray(): array {
    return [
      'id' => $this->id,
      'track_id' => $this->trackId,

      'customer_id' => $this->customerId,

      'created_by_id' => $this->createdById,
      'created_by_type' => $this->createdByType->value,

      'purchase_verification_id' => $this->purchaseVerificationId,

      'department_id' => $this->departmentId,

      'assigned_agent_id' => $this->assignedAgentId,

      'subject' => $this->subject,

      'status' => $this->status->value,
      'state' => $this->state->value,
      'priority' => $this->priority->value,
      'source' => $this->source->value,

      'last_message_id' => $this->lastMessageId,

      'last_reply_at' => $this->lastReplyAt,
      'first_response_at' => $this->firstResponseAt,
      'resolved_at' => $this->resolvedAt,
      'closed_at' => $this->closedAt,
      'reopened_at' => $this->reopenedAt,

      'is_public' => $this->isPublic,
      'public_token' => $this->publicToken,

      'metadata' => $this->metadata,

      'created_at' => $this->createdAt,
      'updated_at' => $this->updatedAt,
    ];
  }

  // -------------------------
  // Getters
  // -------------------------

  public function id(): int {
    return $this->id;
  }

  public function customerId(): int {
    return $this->customerId;
  }

  public function departmentId(): int {
    return $this->departmentId;
  }

  public function assignedAgentId(): ?int {
    return $this->assignedAgentId;
  }

  public function subject(): string {
    return $this->subject;
  }

  public function status(): TicketStatus {
    return $this->status;
  }

  public function state(): TicketState {
    return $this->state;
  }

  public function priority(): TicketPriority {
    return $this->priority;
  }

  public function source(): SourceType {
    return $this->source;
  }

  public function metadata(): ?string {
    return $this->metadata;
  }

  public function createdAt(): string {
    return $this->createdAt;
  }

  public function updatedAt(): ?string {
    return $this->updatedAt;
  }

  // --------------------------------------------------
  // Domain Methods
  // --------------------------------------------------

  public function isOpen(): bool {
    return $this->status === TicketStatus::OPEN;
  }

  public function isPending(): bool {
    return $this->status === TicketStatus::PENDING;
  }

  public function isResolved(): bool {
    return $this->status === TicketStatus::RESOLVED;
  }

  public function isClosed(): bool {
    return $this->status === TicketStatus::CLOSED;
  }

  public function isActive(): bool {
    return $this->state === TicketState::ACTIVE;
  }

  public function isArchived(): bool {
    return $this->state === TicketState::ARCHIVED;
  }

  public function isAssigned(): bool {
    return $this->assignedAgentId !== null;
  }

  public function isUnassigned(): bool {
    return $this->assignedAgentId === null;
  }

  public function isHighPriority(): bool {
    return $this->priority === TicketPriority::HIGH;
  }

  public function isUrgent(): bool {
    return $this->priority === TicketPriority::URGENT;
  }

  public function hasCustomer(): bool {
    return $this->customerId !== null;
  }

  public function hasPurchaseVerification(): bool {
    return $this->purchaseVerificationId !== null;
  }

  public function hasReplies(): bool {
    return $this->lastMessageId !== null;
  }

  public function hasFirstResponse(): bool {
    return $this->firstResponseAt !== null;
  }

  public function isPublic(): bool {
    return $this->isPublic;
  }

  public function hasPublicToken(): bool {
    return !empty($this->publicToken);
  }

  public function canBeReopened(): bool {
    return $this->isResolved() || $this->isClosed();
  }
}
