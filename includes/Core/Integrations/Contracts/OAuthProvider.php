<?php

declare(strict_types=1);

namespace SupportBay\Core\Integrations\Contracts;

use SupportBay\Core\Integrations\Data\OAuthLoginData;

interface OAuthProvider {
  /**
   * Build the provider authorization URL.
   *
   * @param array<string, mixed> $context
   */
  public function authorizationUrl(
    array $context,
  ): string;

  /**
   * Exchange a callback code for normalized login data.
   *
   * @param array<string, mixed> $context
   */
  public function authenticateOAuth(
    string $code,
    array $context,
  ): OAuthLoginData;
}
