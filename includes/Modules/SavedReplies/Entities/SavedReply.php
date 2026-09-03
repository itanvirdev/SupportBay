<?php

declare(strict_types=1);

namespace SupportBay\Modules\SavedReplies\Entities;

use SupportBay\Core\Entities\Entity;
use SupportBay\Modules\SavedReplies\Enums\SavedReplyStatus;

final class SavedReply extends Entity {
  public function __construct(
    private readonly int $id,
    private readonly string $title,
    private readonly string $content,
    private readonly ?string $category,
    private readonly SavedReplyStatus $status,
    private readonly int $createdBy,
    private readonly int $usageCount,
    private readonly ?string $lastUsedAt,
    private readonly ?int $lastUsedBy,
    private readonly string $createdAt,
    private readonly string $updatedAt,
  ) {
  }

  public function toArray(): array {
    return ['id' => $this->id, 'title' => $this->title, 'content' => $this->content, 'category' => $this->category,
      'status' => $this->status->value, 'created_by' => $this->createdBy,
      'usage_count' => $this->usageCount, 'last_used_at' => $this->lastUsedAt,
      'last_used_by' => $this->lastUsedBy,
      'created_at' => $this->createdAt, 'updated_at' => $this->updatedAt];
  }

  public function id(): int { return $this->id; }
  public function title(): string { return $this->title; }
  public function content(): string { return $this->content; }
  public function category(): ?string { return $this->category; }
  public function status(): SavedReplyStatus { return $this->status; }
  public function createdBy(): int { return $this->createdBy; }
  public function usageCount(): int { return $this->usageCount; }
  public function lastUsedAt(): ?string { return $this->lastUsedAt; }
  public function lastUsedBy(): ?int { return $this->lastUsedBy; }
  public function createdAt(): string { return $this->createdAt; }
  public function updatedAt(): string { return $this->updatedAt; }
  public function isActive(): bool { return $this->status === SavedReplyStatus::ACTIVE; }
}
