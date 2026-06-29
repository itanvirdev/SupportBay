<?php

declare(strict_types=1);

namespace SupportBay\Modules\Activities\Entities;

use SupportBay\Common\Enums\AuthorType;
use SupportBay\Core\Entities\Entity;
use SupportBay\Modules\Activities\Enums\ActivityType;

final class Activity extends Entity {
  public function __construct(
    private int $id,

    private int $ticketId,

    private ?int $actorId,

    private AuthorType $actorType,

    private ActivityType $eventType,

    private ?string $description,

    private ?string $payload,

    private ?string $ipAddress,

    private string $createdAt,
  ) {
  }

  /**
   * Convert entity to array.
   */
  public function toArray(): array {
    return [
      'id'          => $this->id,
      'ticket_id'   => $this->ticketId,
      'actor_id'    => $this->actorId,
      'actor_type'  => $this->actorType->value,
      'event_type'  => $this->eventType->value,
      'description' => $this->description,
      'payload'     => $this->payload,
      'ip_address'  => $this->ipAddress,
      'created_at'  => $this->createdAt,
    ];
  }

  // -------------------------
  // Getters
  // -------------------------

  public function id(): int {
    return $this->id;
  }

  public function ticketId(): ?int {
    return $this->ticketId;
  }

  public function actorId(): ?int {
    return $this->actorId;
  }

  public function actorType(): AuthorType {
    return $this->actorType;
  }

  public function eventType(): ActivityType {
    return $this->eventType;
  }

  public function description(): ?string {
    return $this->description;
  }

  public function payload(): ?string {
    return $this->payload;
  }

  public function ipAddress(): ?string {
    return $this->ipAddress;
  }

  public function createdAt(): string {
    return $this->createdAt;
  }

  // -------------------------
  // Domain Methods
  // -------------------------

  /**
   * Was activity created by system?
   */
  public function isSystem(): bool {
    return $this->actorType === AuthorType::SYSTEM;
  }

  /**
   * Was activity created by AI?
   */
  public function isAi(): bool {
    return $this->actorType === AuthorType::AI;
  }

  /**
   * Was activity created by customer?
   */
  public function isCustomer(): bool {
    return $this->actorType === AuthorType::CUSTOMER;
  }

  /**
   * Was activity created by staff?
   */
  public function isStaff(): bool {
    return in_array(
      $this->actorType,
      [
        AuthorType::AGENT,
        AuthorType::MANAGER,
      ],
      true
    );
  }

  /**
   * Has payload data?
   */
  public function hasPayload(): bool {
    return ! empty($this->payload);
  }
}
