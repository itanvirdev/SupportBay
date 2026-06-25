<?php

declare(strict_types=1);

namespace SupportBay\Modules\Attachments\Enums;

enum AttachmentCategory: string {
  case IMAGE = 'image';

  case VIDEO = 'video';

  case AUDIO = 'audio';

  case DOCUMENT = 'document';

  case ARCHIVE = 'archive';

  case PDF = 'pdf';

  case CSV = 'csv';

  case JSON = 'json';

  case MEDICAL = 'medical';

  case THREE_D = '3d';

  case OTHER = 'other';

  /**
   * Default category.
   */
  public static function default(): self {
    return self::DOCUMENT;
  }

  /**
   * Is image?
   */
  public function isImage(): bool {
    return $this === self::IMAGE;
  }

  /**
   * Is video?
   */
  public function isVideo(): bool {
    return $this === self::VIDEO;
  }

  /**
   * Is audio?
   */
  public function isAudio(): bool {
    return $this === self::AUDIO;
  }

  /**
   * Is document?
   */
  public function isDocument(): bool {
    return in_array(
      $this,
      [
        self::DOCUMENT,
        self::PDF,
        self::CSV,
        self::JSON,
        self::MEDICAL,
      ],
      true
    );
  }

  /**
   * Is media?
   */
  public function isMedia(): bool {
    return in_array(
      $this,
      [
        self::IMAGE,
        self::VIDEO,
        self::AUDIO,
      ],
      true
    );
  }
}
