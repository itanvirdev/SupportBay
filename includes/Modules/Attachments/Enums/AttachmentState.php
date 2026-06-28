<?php

declare(strict_types=1);

namespace SupportBay\Modules\Attachments\Enums;

enum AttachmentState: string {
  case ACTIVE = 'active';

  case DELETED = 'deleted';

  case QUARANTINED = 'quarantined';

  /**
   * Default state.
   */
  public static function default(): self {
    return self::ACTIVE;
  }

  /**
   * Is active?
   */
  public function isActive(): bool {
    return $this === self::ACTIVE;
  }

  /**
   * Is deleted?
   */
  public function isDeleted(): bool {
    return $this === self::DELETED;
  }

  /**
   * Is quarantined?
   */
  public function isQuarantined(): bool {
    return $this === self::QUARANTINED;
  }
}
