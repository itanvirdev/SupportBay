<?php

declare(strict_types=1);

namespace SupportBay\Modules\Auth\Services;

use SupportBay\Modules\Auth\Entities\AuthToken;
use SupportBay\Modules\Auth\Enums\AuthTokenState;
use SupportBay\Modules\Auth\Enums\AuthTokenType;
use SupportBay\Modules\Auth\Repositories\AuthTokenRepository;

final class AuthService {
  public function __construct(
    private AuthTokenRepository $tokens,
  ) {
  }

  /**
   * Generate authentication token.
   */
  public function generate(
    int $userId,
    AuthTokenType $type = AuthTokenType::MAGIC_LOGIN,
    ?string $redirectTo = null,
    int $expiresIn = DAY_IN_SECONDS * 30,
  ): string {

    $token = bin2hex(random_bytes(32));

    $this->tokens->create([
      'user_id'      => $userId,
      'type'         => $type->value,
      'state'        => AuthTokenState::ACTIVE->value,
      'token_hash'   => hash('sha256', $token),
      'redirect_to'  => $redirectTo,
      'use_count'    => 0,
      'max_uses'     => null,
      'expires_at'   => gmdate(
        'Y-m-d H:i:s',
        time() + $expiresIn
      ),
      'ip_address'   => $_SERVER['REMOTE_ADDR'] ?? null,
      'user_agent'   => $_SERVER['HTTP_USER_AGENT'] ?? null,
      'created_at'   => current_time('mysql'),
      'updated_at'   => current_time('mysql'),
    ]);

    return $token;
  }

  /**
   * Validate raw token.
   */
  public function validate(string $token): ?AuthToken {

    $hash = hash('sha256', $token);

    $authToken = $this->tokens->findByHash($hash);

    if (! $authToken) {
      return null;
    }

    if ($authToken->state() !== AuthTokenState::ACTIVE) {
      return null;
    }

    if (strtotime($authToken->expiresAt()) < time()) {
      return null;
    }

    return $authToken;
  }

  /**
   * Authenticate user.
   */
  public function authenticate(string $token): bool {

    $authToken = $this->validate($token);

    if (! $authToken) {
      return false;
    }

    wp_set_current_user($authToken->userId());

    wp_set_auth_cookie($authToken->userId());

    $this->tokens->update(
      $authToken->id(),
      [
        'last_used_at' => current_time('mysql'),
      ]
    );

    return true;
  }

  /**
   * Revoke token.
   */
  public function revoke(int $tokenId): bool {

    return $this->tokens->update(
      $tokenId,
      [
        'state'       => AuthTokenState::REVOKED->value,
        'revoked_at'  => current_time('mysql'),
        'updated_at'  => current_time('mysql'),
      ]
    );
  }

  /**
   * Revoke all active tokens.
   */
  public function revokeAll(int $userId): void {

    foreach ($this->tokens->getActiveByUser($userId) as $token) {

      $this->revoke($token->id());
    }
  }

  /**
   * Find token.
   */
  public function find(int $id): ?AuthToken {
    return $this->tokens->find($id);
  }
}
