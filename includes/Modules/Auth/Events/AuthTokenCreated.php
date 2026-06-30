<?php

declare(strict_types=1);

namespace SupportBay\Modules\Auth\Events;

use SupportBay\Core\Events\AbstractEvent;
use SupportBay\Modules\Auth\Entities\AuthToken;

final class AuthTokenCreated extends AbstractEvent {
  /**
   * Constructor.
   */
  public function __construct(
    private readonly AuthToken $token,
  ) {
  }

  /**
   * Get the authentication token.
   */
  public function token(): AuthToken {
    return $this->token;
  }
}
