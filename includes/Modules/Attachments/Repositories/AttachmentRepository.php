<?php

declare(strict_types=1);

namespace SupportBay\Modules\Attachments\Repositories;

use SupportBay\Core\Database\Repository;
use SupportBay\Common\Enums\AuthorType;
use SupportBay\Modules\Attachments\Entities\Attachment;
use SupportBay\Modules\Attachments\Enums\AttachmentCategory;
use SupportBay\Modules\Attachments\Enums\AttachmentState;
use SupportBay\Modules\Attachments\Enums\ScanStatus;
use SupportBay\Modules\Attachments\Enums\StorageDisk;
use SupportBay\Modules\Attachments\Database\AttachmentSchema;

final class AttachmentRepository extends Repository {
  /**
   * Table name.
   */
  protected function table(): string {
    return AttachmentSchema::tableName();
  }

  /**
   * Hydrate entity.
   */
  protected function hydrate(array $row): Attachment {
    return new Attachment(
      id: (int) $row['id'],

      messageId: (int) $row['message_id'],
      ticketId: (int) $row['ticket_id'],

      uploadedById: isset($row['uploaded_by_id'])
        ? (int) $row['uploaded_by_id']
        : null,

      uploadedByType: AuthorType::from($row['uploaded_by_type']),

      disk: StorageDisk::from($row['disk']),

      originalName: $row['original_name'],
      storedName: $row['stored_name'],
      path: $row['path'],

      fileSize: (int) $row['file_size'],

      extension: $row['extension'],
      mimeType: $row['mime_type'],

      category: AttachmentCategory::from($row['category']),

      checksum: $row['checksum'],

      width: isset($row['width'])
        ? (int) $row['width']
        : null,

      height: isset($row['height'])
        ? (int) $row['height']
        : null,

      duration: isset($row['duration'])
        ? (float) $row['duration']
        : null,

      isPreviewable: (bool) $row['is_previewable'],

      scanStatus: ScanStatus::from($row['scan_status']),

      state: AttachmentState::from($row['state']),

      downloadCount: (int) $row['download_count'],

      lastAccessedAt: $row['last_accessed_at'],

      metadata: $row['metadata'],

      createdAt: $row['created_at'],

      updatedAt: $row['updated_at'],
    );
  }

  /**
   * Create attachment.
   */
  public function create(array $data): int {
    return $this->insert($data);
  }

  /**
   * Find attachment.
   */
  public function find(int $id): ?Attachment {
    return $this->findById($id);
  }

  /**
   * Find by stored filename.
   */
  public function findByStoredName(string $storedName): ?Attachment {
    return $this->first([
      'stored_name' => $storedName,
    ]);
  }

  /**
   * Find by checksum.
   */
  public function findByChecksum(string $checksum): ?Attachment {
    return $this->first([
      'checksum' => $checksum,
    ]);
  }

  /**
   * Find attachments for a message.
   *
   * @return Attachment[]
   */
  public function findByMessage(int $messageId): array {
    return $this->findWhere([
      'message_id' => $messageId,
    ]);
  }

  /**
   * Find attachments for a ticket.
   *
   * @return Attachment[]
   */
  public function findByTicket(int $ticketId): array {
    return $this->findWhere([
      'ticket_id' => $ticketId,
    ]);
  }

  /**
   * Find active attachments.
   *
   * @return Attachment[]
   */
  public function findActive(): array {
    return $this->findWhere([
      'state' => AttachmentState::ACTIVE->value,
    ]);
  }

  /**
   * Find quarantined attachments.
   *
   * @return Attachment[]
   */
  public function findQuarantined(): array {
    return $this->findWhere([
      'state' => AttachmentState::QUARANTINED->value,
    ]);
  }

  /**
   * Find previewable attachments.
   *
   * @return Attachment[]
   */
  public function findPreviewable(): array {
    return $this->findWhere([
      'is_previewable' => 1,
    ]);
  }

  /**
   * Update attachment.
   */
  public function update(int $id, array $data): bool {
    return $this->updateById($id, $data);
  }

  /**
   * Delete attachment.
   */
  public function delete(int $id): bool {
    return $this->deleteById($id);
  }

  /**
   * Increment download counter.
   */
  public function incrementDownloadCount(int $id): bool {
    return $this->db->query(
      $this->db->prepare(
        "UPDATE {$this->table()}
                 SET download_count = download_count + 1
                 WHERE id = %d",
        $id
      )
    ) !== false;
  }

  /**
   * Update last accessed timestamp.
   */
  public function touchLastAccessed(int $id): bool {
    return $this->updateById($id, [
      'last_accessed_at' => current_time('mysql'),
    ]);
  }

  /**
   * Check checksum existence.
   */
  public function existsChecksum(string $checksum): bool {
    return $this->findByChecksum($checksum) !== null;
  }
}
