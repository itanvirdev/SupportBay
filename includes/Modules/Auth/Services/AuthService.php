<?php

declare(strict_types=1);

namespace SupportBay\Modules\Auth\Services;

use SupportBay\Core\Events\EventDispatcher;
use SupportBay\Modules\Auth\Entities\AuthToken;
use SupportBay\Modules\Auth\Enums\AuthTokenState;
use SupportBay\Modules\Auth\Enums\AuthTokenType;
use SupportBay\Modules\Auth\Events\AuthTokenAuthenticated;
use SupportBay\Modules\Auth\Events\AuthTokenCreated;
use SupportBay\Modules\Auth\Events\AuthTokenRevoked;
use SupportBay\Modules\Auth\Repositories\AuthTokenRepository;

final class AuthService {
  /**
   * Repository.
   */
  public function __construct(
    private readonly AuthTokenRepository $repository,
    private readonly EventDispatcher $events
  ) {
  }

  /**
   * Generate a new authentication token.
   *
   * Returns the plain token.
   */
  public function generate(
    int $userId,
    ?string $redirectTo = null,
    AuthTokenType $type = AuthTokenType::MAGIC_LOGIN,
    int $expiresIn = DAY_IN_SECONDS * 30
  ): string {

    $token = bin2hex(random_bytes(32));

    $id = $this->repository->create([
      'user_id'      => $userId,
      'type'         => $type->value,
      'state'        => AuthTokenState::ACTIVE->value,
      'token_hash'   => hash('sha256', $token),
      'redirect_to'  => $redirectTo,
      'use_count'    => 0,
      'max_uses'     => null,
      'expires_at'   => gmdate('Y-m-d H:i:s', time() + $expiresIn),
      'ip_address'   => $_SERVER['REMOTE_ADDR'] ?? null,
      'user_agent'   => $_SERVER['HTTP_USER_AGENT'] ?? null,
      'created_at'   => current_time('mysql'),
      'updated_at'   => current_time('mysql'),
    ]);

    $entity = $this->repository->find($id);

    $this->events->dispatch(
      new AuthTokenCreated($entity)
    );

    return $token;
  }

  /**
   * Find token by ID.
   */
  public function find(int $id): ?AuthToken {
    /** @var AuthToken|null */
    return $this->repository->find($id);
  }

  /**
   * Validate a plain token.
   */
  public function validate(string $token): ?AuthToken {

    $hash = hash('sha256', $token);

    $authToken = $this->repository->findByHash($hash);

    if (! $authToken) {
      return null;
    }

    if ($authToken->state() !== AuthTokenState::ACTIVE) {
      return null;
    }

    if (strtotime($authToken->expiresAt()) < time()) {
      return null;
    }

    if (
      $authToken->maxUses() !== null &&
      $authToken->useCount() >= $authToken->maxUses()
    ) {
      return null;
    }

    return $authToken;
  }

  /**
   * Authenticate using token.
   */
  public function authenticate(string $token): bool {

    $authToken = $this->validate($token);

    if (! $authToken) {
      return false;
    }

    wp_set_current_user($authToken->userId());

    wp_set_auth_cookie($authToken->userId(), true);

    $this->repository->update(
      $authToken->id(),
      [
        'use_count'    => $authToken->useCount() + 1,
        'last_used_at' => current_time('mysql'),
        'updated_at'   => current_time('mysql'),
      ]
    );

    $this->events->dispatch(
      new AuthTokenAuthenticated($authToken->id())
    );

    return true;
  }

  /**
   * Revoke token.
   */
  public function revoke(
    int $tokenId,
    ?int $revokedBy = null
  ): bool {

    $result = $this->repository->update(
      $tokenId,
      [
        'state'       => AuthTokenState::REVOKED->value,
        'revoked_at'  => current_time('mysql'),
        'revoked_by'  => $revokedBy,
        'updated_at'  => current_time('mysql'),
      ]
    );

    if ($result) {
      $this->events->dispatch(
        new AuthTokenRevoked($tokenId)
      );
    }

    return $result;
  }

  /**
   * Revoke all active tokens for a user.
   */
  public function revokeUserTokens(
    int $userId,
    ?int $revokedBy = null
  ): void {

    foreach ($this->repository->findActiveByUser($userId) as $token) {

      $this->revoke(
        $token->id(),
        $revokedBy
      );
    }
  }

  /**
   * Purge expired tokens.
   */
  public function purgeExpired(): int {

    global $wpdb;

    return (int) $wpdb->query(
      $wpdb->prepare(
        "DELETE
         FROM {$wpdb->prefix}sbay_auth_tokens
         WHERE expires_at < %s",
        current_time('mysql')
      )
    );
  }
}
