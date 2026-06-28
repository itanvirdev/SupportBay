<?php

declare(strict_types=1);

namespace SupportBay\Modules\Attachments\Entities;

use SupportBay\Core\Entities\Entity;
use SupportBay\Common\Enums\AuthorType;
use SupportBay\Modules\Attachments\Enums\AttachmentCategory;
use SupportBay\Modules\Attachments\Enums\AttachmentState;
use SupportBay\Modules\Attachments\Enums\ScanStatus;
use SupportBay\Modules\Attachments\Enums\StorageDisk;

final class Attachment extends Entity {
  public function __construct(
    private int $id,

    private int $messageId,
    private int $ticketId,

    private ?int $uploadedById,
    private AuthorType $uploadedByType,

    private StorageDisk $disk,

    private string $originalName,
    private string $storedName,
    private string $path,

    private int $fileSize,

    private string $extension,
    private string $mimeType,

    private AttachmentCategory $category,

    private ?string $checksum,

    private ?int $width,
    private ?int $height,

    private ?float $duration,

    private bool $isPreviewable,

    private ScanStatus $scanStatus,

    private AttachmentState $state,

    private int $downloadCount,

    private ?string $lastAccessedAt,

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
      'id'                => $this->id,
      'message_id'        => $this->messageId,
      'ticket_id'         => $this->ticketId,
      'uploaded_by_id'       => $this->uploadedById,
      'uploaded_by_type'       => $this->uploadedByType,
      'disk'              => $this->disk->value,
      'original_name'     => $this->originalName,
      'stored_name'       => $this->storedName,
      'path'              => $this->path,
      'file_size'         => $this->fileSize,
      'extension'         => $this->extension,
      'mime_type'         => $this->mimeType,
      'category'          => $this->category->value,
      'checksum'          => $this->checksum,
      'width'             => $this->width,
      'height'            => $this->height,
      'duration'          => $this->duration,
      'is_previewable'    => $this->isPreviewable,
      'scan_status'       => $this->scanStatus->value,
      'state'             => $this->state->value,
      'download_count'    => $this->downloadCount,
      'last_accessed_at'  => $this->lastAccessedAt,
      'metadata'          => $this->metadata,
      'created_at'        => $this->createdAt,
      'updated_at'        => $this->updatedAt,
    ];
  }

  // -----------------------------------------------------------------
  // Getters
  // -----------------------------------------------------------------

  public function id(): int {
    return $this->id;
  }

  public function messageId(): int {
    return $this->messageId;
  }

  public function ticketId(): int {
    return $this->ticketId;
  }

  public function uploadedById(): ?int {
    return $this->uploadedById;
  }

  public function uploadedByType(): AuthorType {
    return $this->uploadedByType;
  }

  public function disk(): StorageDisk {
    return $this->disk;
  }

  public function originalName(): string {
    return $this->originalName;
  }

  public function storedName(): string {
    return $this->storedName;
  }

  public function path(): string {
    return $this->path;
  }

  public function fileSize(): int {
    return $this->fileSize;
  }

  public function extension(): string {
    return $this->extension;
  }

  public function mimeType(): string {
    return $this->mimeType;
  }

  public function category(): AttachmentCategory {
    return $this->category;
  }

  public function checksum(): ?string {
    return $this->checksum;
  }

  public function width(): ?int {
    return $this->width;
  }

  public function height(): ?int {
    return $this->height;
  }

  public function duration(): ?float {
    return $this->duration;
  }

  public function isPreviewable(): bool {
    return $this->isPreviewable;
  }

  public function scanStatus(): ScanStatus {
    return $this->scanStatus;
  }

  public function state(): AttachmentState {
    return $this->state;
  }

  public function downloadCount(): int {
    return $this->downloadCount;
  }

  public function lastAccessedAt(): ?string {
    return $this->lastAccessedAt;
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

    // -----------------------------------------------------------------
    // Domain Methods
    // -----------------------------------------------------------------

  /**
   * Is attachment active?
   */
  public function isActive(): bool {
    return $this->state->isActive();
  }

  /**
   * Is attachment deleted?
   */
  public function isDeleted(): bool {
    return $this->state->isDeleted();
  }

  /**
   * Is quarantined?
   */
  public function isQuarantined(): bool {
    return $this->state->isQuarantined();
  }

  /**
   * Is scan pending?
   */
  public function isScanPending(): bool {
    return $this->scanStatus->isPending();
  }

  /**
   * Is clean?
   */
  public function isClean(): bool {
    return $this->scanStatus->isClean();
  }

  /**
   * Is infected?
   */
  public function isInfected(): bool {
    return $this->scanStatus->isInfected();
  }

  /**
   * Is image?
   */
  public function isImage(): bool {
    return $this->category->isImage();
  }

  /**
   * Is media?
   */
  public function isMedia(): bool {
    return $this->category->isMedia();
  }

  /**
   * Is document?
   */
  public function isDocument(): bool {
    return $this->category->isDocument();
  }

  /**
   * Stored locally?
   */
  public function isStoredLocally(): bool {
    return $this->disk->isLocal();
  }

  /**
   * Stored on cloud?
   */
  public function isStoredInCloud(): bool {
    return $this->disk->isCloud();
  }

  /**
   * Has checksum?
   */
  public function hasChecksum(): bool {
    return ! empty($this->checksum);
  }

  /**
   * Has preview?
   */
  public function canPreview(): bool {
    return $this->isPreviewable;
  }
}
