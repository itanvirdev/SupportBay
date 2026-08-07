<?php

declare(strict_types=1);

namespace SupportBay\Modules\Attachments\Services;

use InvalidArgumentException;
use RuntimeException;
use SupportBay\Modules\Attachments\Entities\Attachment;
use SupportBay\Modules\Attachments\Enums\AttachmentCategory;
use SupportBay\Modules\Attachments\Enums\AttachmentState;
use SupportBay\Modules\Attachments\Enums\ScanStatus;
use SupportBay\Modules\Attachments\Enums\StorageDisk;
use SupportBay\Modules\Attachments\Events\AttachmentUploaded;
use SupportBay\Modules\Attachments\Repositories\AttachmentRepository;
use SupportBay\Core\Events\EventDispatcher;

final class AttachmentService {
  private const MAX_FILE_SIZE = 10485760;

  /** @var array<string, string> */
  private const ALLOWED_TYPES = [
    'jpg|jpeg|jpe' => 'image/jpeg',
    'png'          => 'image/png',
    'gif'          => 'image/gif',
    'webp'         => 'image/webp',
    'pdf'          => 'application/pdf',
    'doc'          => 'application/msword',
    'docx'         => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'xls'          => 'application/vnd.ms-excel',
    'xlsx'         => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'txt'          => 'text/plain',
    'csv'          => 'text/csv',
    'zip'          => 'application/zip',
  ];

  public function __construct(
    private readonly AttachmentRepository $attachments,
    private readonly EventDispatcher $events,
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
   * Validate and store an uploaded local file with its metadata.
   *
   * @param array<string, mixed> $file
   * @param array<string, mixed> $data
   */
  public function storeUploadedFile(
    array $file,
    array $data,
  ): Attachment {
    $this->validateUploadedFile($file);

    $originalName = sanitize_file_name((string) $file['name']);
    $checkedType = wp_check_filetype_and_ext(
      (string) $file['tmp_name'],
      $originalName,
      self::ALLOWED_TYPES,
    );

    if (empty($checkedType['ext']) || empty($checkedType['type'])) {
      throw new InvalidArgumentException(
        'This file type is not allowed.'
      );
    }

    $directory = $this->storageDirectory();
    $storedName = bin2hex(random_bytes(16)) . '.' . $checkedType['ext'];
    $destination = trailingslashit($directory) . $storedName;

    if (! move_uploaded_file((string) $file['tmp_name'], $destination)) {
      throw new RuntimeException('The uploaded file could not be stored.');
    }

    $attachmentId = 0;

    try {
      $attachmentId = $this->upload(array_merge($data, [
        'original_name' => $originalName,
        'stored_name'   => $storedName,
        'path'          => $destination,
        'file_size'     => (int) $file['size'],
        'extension'     => (string) $checkedType['ext'],
        'mime_type'     => (string) $checkedType['type'],
        'checksum'      => hash_file('sha256', $destination) ?: null,
        'is_previewable' => in_array(
          (string) $checkedType['ext'],
          ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'],
          true,
        ),
      ]));
    } catch (\Throwable $exception) {
      wp_delete_file($destination);

      throw $exception;
    }

    $attachment = $this->find($attachmentId);

    if (! $attachment) {
      wp_delete_file($destination);
      throw new RuntimeException('Attachment metadata could not be stored.');
    }

    return $attachment;
  }

  /**
   * Find attachment.
   */
  public function find(int $id): ?Attachment {
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
   * Permanently remove an attachment and its local file.
   */
  public function permanentlyDelete(int $id): bool {
    $attachment = $this->attachments->find($id);

    if (! $attachment) {
      return false;
    }

    if ($attachment->isStoredLocally()) {
      wp_delete_file($attachment->path());
    }

    return $this->attachments->delete($id);
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

  /**
   * Validate the PHP upload envelope.
   *
   * @param array<string, mixed> $file
   */
  private function validateUploadedFile(array $file): void {
    if (
      ! isset($file['name'], $file['tmp_name'], $file['size'], $file['error']) ||
      (int) $file['error'] !== UPLOAD_ERR_OK ||
      ! is_uploaded_file((string) $file['tmp_name'])
    ) {
      throw new InvalidArgumentException('A valid uploaded file is required.');
    }

    if ((int) $file['size'] <= 0 || (int) $file['size'] > self::MAX_FILE_SIZE) {
      throw new InvalidArgumentException(
        'Each attachment must be smaller than 10 MB.'
      );
    }
  }

  /**
   * Resolve and protect the local attachment directory.
   */
  private function storageDirectory(): string {
    $uploads = wp_upload_dir();

    if (! empty($uploads['error'])) {
      throw new RuntimeException((string) $uploads['error']);
    }

    $directory = trailingslashit((string) $uploads['basedir'])
      . 'supportbay/' . current_time('Y/m');

    if (! wp_mkdir_p($directory)) {
      throw new RuntimeException('Attachment storage is unavailable.');
    }

    $root = trailingslashit((string) $uploads['basedir']) . 'supportbay';
    $this->protectDirectory($root);

    return $directory;
  }

  /**
   * Add server-level and index protection to local storage.
   */
  private function protectDirectory(string $directory): void {
    $rules = "Options -Indexes\n<FilesMatch \".*\">\nRequire all denied\n</FilesMatch>\n";

    if (! file_exists(trailingslashit($directory) . '.htaccess')) {
      $written = file_put_contents(
        trailingslashit($directory) . '.htaccess',
        $rules,
        LOCK_EX,
      );

      if ($written === false) {
        throw new RuntimeException('Attachment storage could not be protected.');
      }
    }

    if (! file_exists(trailingslashit($directory) . 'index.php')) {
      $written = file_put_contents(
        trailingslashit($directory) . 'index.php',
        "<?php\n// Silence is golden.\n",
        LOCK_EX,
      );

      if ($written === false) {
        throw new RuntimeException('Attachment storage could not be protected.');
      }
    }
  }
}
