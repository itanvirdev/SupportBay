<?php

declare(strict_types=1);

namespace SupportBay\Modules\Auth\Services;

use RuntimeException;
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
   * Constructor.
   */
  public function __construct(
    private readonly AuthTokenRepository $repository,
    private readonly EventDispatcher $events,
  ) {
  }

  /**
   * Generate a new authentication token.
   *
   * Returns the plain token.
   */
  public function generate(
    int $userId,
    AuthTokenType $type = AuthTokenType::MAGIC_LOGIN,
    ?string $redirectTo = null,
    ?string $expiresAt = null,
  ): string {

    $plainToken = bin2hex(random_bytes(32));

    $hash = hash('sha256', $plainToken);

    $id = $this->repository->create([
      'user_id'      => $userId,
      'type'         => $type->value,
      'state'        => AuthTokenState::ACTIVE->value,
      'token_hash'   => $hash,
      'redirect_to'  => $redirectTo,
      'use_count'    => 0,
      'max_uses'     => null,
      'expires_at'   => $expiresAt,
      'created_at'   => current_time('mysql'),
      'updated_at'   => current_time('mysql'),
    ]);

    $token = $this->find($id);

    if (! $token) {
      throw new RuntimeException('Unable to load generated auth token.');
    }

    $this->events->dispatch(
      new AuthTokenCreated($token)
    );

    return $plainToken;
  }

  /**
   * Find token by ID.
   */
  public function find(int $id): ?AuthToken {
    return $this->repository->find($id);
  }

  /**
   * Find token using plain token.
   */
  public function findByToken(string $plainToken): ?AuthToken {
    return $this->repository->findByHash(
      hash('sha256', $plainToken)
    );
  }

  /**
   * Authenticate token.
   */
  public function authenticate(string $plainToken): ?AuthToken {

    $token = $this->findByToken($plainToken);

    if (! $token) {
      return null;
    }

    if ($token->state() !== AuthTokenState::ACTIVE) {
      return null;
    }

    if (
      strtotime($token->expiresAt()) < time()
    ) {
      return null;
    }

    $this->repository->update(
      $token->id(),
      [
        'use_count'    => $token->useCount() + 1,
        'last_used_at' => current_time('mysql'),
        'updated_at'   => current_time('mysql'),
      ]
    );

    $token = $this->find($token->id());

    if ($token) {
      $this->events->dispatch(
        new AuthTokenAuthenticated($token)
      );
    }

    return $token;
  }

  /**
   * Revoke a token.
   */
  public function revoke(
    int $id,
    ?int $revokedBy = null,
  ): bool {

    $success = $this->repository->update(
      $id,
      [
        'state'       => AuthTokenState::REVOKED->value,
        'revoked_at'  => current_time('mysql'),
        'revoked_by'  => $revokedBy,
        'updated_at'  => current_time('mysql'),
      ]
    );

    if (! $success) {
      return false;
    }

    $token = $this->find($id);

    if ($token) {
      $this->events->dispatch(
        new AuthTokenRevoked($token)
      );
    }

    return true;
  }

  /**
   * Revoke all active tokens for a user.
   */
  public function revokeAll(
    int $userId,
    AuthTokenType $type = AuthTokenType::MAGIC_LOGIN,
  ): void {

    foreach ($this->repository->getActiveByUser($userId) as $token) {

      if ($token->type() !== $type) {
        continue;
      }

      $this->revoke($token->id());
    }
  }
}
