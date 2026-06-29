<?php

declare(strict_types=1);

namespace SupportBay\Modules\Auth\Events;

use SupportBay\Core\Events\AbstractEvent;
use SupportBay\Modules\Auth\Enums\AuthTokenType;

final class AuthTokenRevoked extends AbstractEvent {
  public function __construct(
    public readonly int $tokenId,
    public readonly int $userId,
    public readonly AuthTokenType $type,
  ) {
  }
}
