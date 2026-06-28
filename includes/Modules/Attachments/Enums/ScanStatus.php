<?php

declare(strict_types=1);

namespace SupportBay\Modules\Attachments\Enums;

enum ScanStatus: string {
  case PENDING = 'pending';

  case CLEAN = 'clean';

  case INFECTED = 'infected';

  case FAILED = 'failed';

  /**
   * Default scan status.
   */
  public static function default(): self {
    return self::PENDING;
  }

  /**
   * Is pending?
   */
  public function isPending(): bool {
    return $this === self::PENDING;
  }

  /**
   * Is clean?
   */
  public function isClean(): bool {
    return $this === self::CLEAN;
  }

  /**
   * Is infected?
   */
  public function isInfected(): bool {
    return $this === self::INFECTED;
  }

  /**
   * Has scan failed?
   */
  public function hasFailed(): bool {
    return $this === self::FAILED;
  }

  /**
   * Scan completed?
   */
  public function isCompleted(): bool {
    return $this === self::CLEAN
      || $this === self::INFECTED;
  }
}
