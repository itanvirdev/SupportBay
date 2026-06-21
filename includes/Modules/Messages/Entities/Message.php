<?php

declare(strict_types=1);

namespace SupportBay\Modules\Messages\Entities;

use SupportBay\Modules\Messages\Enums\MessageType;
use SupportBay\Common\Enums\AuthorType;

final class Message {
  public function __construct(
    private int $id,
    private int $ticketId,

    private ?int $authorId,
    private AuthorType $authorType,

    private MessageType $type,

    private string $content,

    private ?int $editedById,
    private ?string $editedAt,

    private ?string $customerReadAt,
    private ?string $staffReadAt,

    private ?string $metadata,

    private string $createdAt,
    private ?string $updatedAt,
  ) {
  }

  // -------------------------
  // Getters
  // -------------------------

  public function id(): int {
    return $this->id;
  }

  public function ticketId(): int {
    return $this->ticketId;
  }

  public function authorId(): ?int {
    return $this->authorId;
  }

  public function authorType(): AuthorType {
    return $this->authorType;
  }

  public function type(): MessageType {
    return $this->type;
  }

  public function content(): string {
    return $this->content;
  }

  public function editedById(): ?int {
    return $this->editedById;
  }

  public function editedAt(): ?string {
    return $this->editedAt;
  }

  public function customerReadAt(): ?string {
    return $this->customerReadAt;
  }

  public function staffReadAt(): ?string {
    return $this->staffReadAt;
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

    // -------------------------
    // Domain Methods
    // -------------------------

  /**
   * Is this message visible to customer?
   */
  public function isVisibleToCustomer(): bool {
    return $this->type->isVisibleToCustomer();
  }

  /**
   * Is this message from a customer?
   */
  public function isFromCustomer(): bool {
    return $this->authorType === AuthorType::CUSTOMER;
  }

  /**
   * Is this a system message?
   */
  public function isSystem(): bool {
    return $this->type === MessageType::SYSTEM;
  }

  /**
   * Is this message edited?
   */
  public function isEdited(): bool {
    return $this->editedAt !== null;
  }

  /**
   * Has customer read this message?
   */
  public function isReadByCustomer(): bool {
    return $this->customerReadAt !== null;
  }

  /**
   * Has staff read this message?
   */
  public function isReadByStaff(): bool {
    return $this->staffReadAt !== null;
  }
}
