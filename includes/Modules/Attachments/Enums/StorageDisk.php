<?php

declare(strict_types=1);

namespace SupportBay\Modules\Attachments\Enums;

enum StorageDisk: string {
  case LOCAL = 'local';

  case S3 = 's3';

  case CLOUDFLARE_R2 = 'cloudflare_r2';

  case DIGITALOCEAN_SPACES = 'digitalocean_spaces';

  case BUNNY_STORAGE = 'bunny_storage';

  /**
   * Default storage disk.
   */
  public static function default(): self {
    return self::LOCAL;
  }

  /**
   * Is local storage?
   */
  public function isLocal(): bool {
    return $this === self::LOCAL;
  }

  /**
   * Is cloud storage?
   */
  public function isCloud(): bool {
    return $this !== self::LOCAL;
  }
}
