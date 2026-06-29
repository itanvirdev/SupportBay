<?php

declare(strict_types=1);

namespace SupportBay\Modules\Auth\Enums;

enum AuthTokenState: string {
  /**
   * Token is valid and can be used.
   */
  case ACTIVE = 'active';

  /**
   * Token has expired.
   */
  case EXPIRED = 'expired';

  /**
   * Token was manually revoked.
   */
  case REVOKED = 'revoked';
}
