<?php

declare(strict_types=1);

namespace SupportBay\Modules\Auth\Services;

use SupportBay\Modules\Auth\Entities\AuthToken;
use SupportBay\Modules\Auth\Enums\AuthTokenType;

final class MagicLoginService {
  public function __construct(
    private readonly AuthService $auth,
  ) {
  }

  /**
   * Consume a magic-login token and establish a WordPress session.
   */
  public function login(string $plainToken): ?AuthToken {
    $token = $this->auth->authenticate(
      trim($plainToken),
      AuthTokenType::MAGIC_LOGIN,
    );

    if (! $token) {
      return null;
    }

    wp_set_current_user($token->userId());
    wp_set_auth_cookie($token->userId(), true);

    $this->auth->revoke($token->id(), $token->userId());

    return $token;
  }
}
