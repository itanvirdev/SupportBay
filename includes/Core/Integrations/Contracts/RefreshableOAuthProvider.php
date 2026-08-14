<?php

declare(strict_types=1);

namespace SupportBay\Core\Integrations\Contracts;

use SupportBay\Core\Integrations\Data\OAuthTokenData;

interface RefreshableOAuthProvider {
  /**
   * Refresh a normalized OAuth token.
   *
   * @param array<string, mixed> $context
   */
  public function refreshOAuthToken(
    OAuthTokenData $token,
    array $context,
  ): OAuthTokenData;
}
