<?php

declare(strict_types=1);

namespace SupportBay\Core\Integrations\Data;

final readonly class OAuthLoginData {
  public function __construct(
    private OAuthIdentityData $identity,
    private OAuthTokenData $token,
  ) {
  }

  public function identity(): OAuthIdentityData {
    return $this->identity;
  }

  public function token(): OAuthTokenData {
    return $this->token;
  }
}
