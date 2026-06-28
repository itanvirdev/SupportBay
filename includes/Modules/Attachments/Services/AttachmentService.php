<?php

declare(strict_types=1);

namespace SupportBay\Modules\Attachments\Services;

use SupportBay\Modules\Attachments\Enums\AttachmentCategory;
use SupportBay\Modules\Attachments\Enums\AttachmentState;
use SupportBay\Modules\Attachments\Enums\ScanStatus;
use SupportBay\Modules\Attachments\Enums\StorageDisk;
use SupportBay\Modules\Attachments\Events\AttachmentUploaded;
use SupportBay\Modules\Attachments\Repositories\AttachmentRepository;
use SupportBay\Core\Events\EventDispatcher;

final class AttachmentService {
  public function __construct(
    private AttachmentRepository $attachments,
    private EventDispatcher $events,
  ) {
  }

  /**
   * Store attachment metadata.
   */
  public function upload(array $data): int {
    $data = $this->normalize($data);

    $id = $this->attachments->create($data);

    if ($attachment = $this->attachments->find($id)) {
      $this->events->dispatch(
        new AttachmentUploaded($attachment)
      );
    }

    return $id;
  }

  /**
   * Find attachment.
   */
  public function find(int $id) {
    return $this->attachments->find($id);
  }

  /**
   * Find attachments by message.
   */
  public function findByMessage(int $messageId): array {
    return $this->attachments->findByMessage($messageId);
  }

  /**
   * Find attachments by ticket.
   */
  public function findByTicket(int $ticketId): array {
    return $this->attachments->findByTicket($ticketId);
  }

  /**
   * Soft delete attachment.
   */
  public function delete(int $id): bool {
    return $this->attachments->update($id, [
      'state' => AttachmentState::DELETED->value,
    ]);
  }

  /**
   * Quarantine attachment.
   */
  public function quarantine(int $id): bool {
    return $this->attachments->update($id, [
      'state' => AttachmentState::QUARANTINED->value,
    ]);
  }

  /**
   * Restore attachment.
   */
  public function restore(int $id): bool {
    return $this->attachments->update($id, [
      'state' => AttachmentState::ACTIVE->value,
    ]);
  }

  /**
   * Record download.
   */
  public function recordDownload(int $id): void {
    $this->attachments->incrementDownloadCount($id);
    $this->attachments->touchLastAccessed($id);
  }

  /**
   * Normalize defaults.
   */
  private function normalize(array $data): array {
    $data['disk'] ??= StorageDisk::default()->value;

    $data['category'] ??= $this->detectCategory(
      $data['extension'] ?? ''
    )->value;

    $data['scan_status'] ??= ScanStatus::default()->value;

    $data['state'] ??= AttachmentState::default()->value;

    $data['is_previewable'] ??= false;

    $data['download_count'] ??= 0;

    $data['stored_name'] ??= $this->generateStoredName(
      $data['original_name'] ?? ''
    );

    return $data;
  }

  /**
   * Generate secure filename.
   */
  private function generateStoredName(string $filename): string {
    $extension = pathinfo(
      $filename,
      PATHINFO_EXTENSION
    );

    return bin2hex(random_bytes(16))
      . ($extension ? ".{$extension}" : '');
  }

  /**
   * Detect attachment category.
   */
  private function detectCategory(string $extension): AttachmentCategory {
    $extension = strtolower($extension);

    return match ($extension) {
      'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'
      => AttachmentCategory::IMAGE,

      'mp4', 'mov', 'avi', 'webm'
      => AttachmentCategory::VIDEO,

      'mp3', 'wav', 'ogg'
      => AttachmentCategory::AUDIO,

      'zip', 'rar', '7z', 'tar', 'gz'
      => AttachmentCategory::ARCHIVE,

      'pdf'
      => AttachmentCategory::PDF,

      'csv'
      => AttachmentCategory::CSV,

      'json'
      => AttachmentCategory::JSON,

      default
      => AttachmentCategory::DOCUMENT,
    };
  }
}
