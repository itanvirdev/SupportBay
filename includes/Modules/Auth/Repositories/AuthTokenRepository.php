<?php

declare(strict_types=1);

namespace SupportBay\Modules\Auth\Repositories;

use SupportBay\Core\Database\Repository;
use SupportBay\Modules\Auth\Entities\AuthToken;
use SupportBay\Modules\Auth\Enums\AuthTokenState;
use SupportBay\Modules\Auth\Enums\AuthTokenType;

final class AuthTokenRepository extends Repository {
  /**
   * Database table.
   */
  protected function table(): string {
    return $this->db->prefix . 'sbay_auth_tokens';
  }

  /**
   * Hydrate entity.
   */
  protected function hydrate(array $row): AuthToken {
    return new AuthToken(
      id: (int) $row['id'],
      userId: (int) $row['user_id'],
      type: AuthTokenType::from($row['type']),
      state: AuthTokenState::from($row['state']),
      tokenHash: $row['token_hash'],
      redirectTo: $row['redirect_to'],
      useCount: (int) $row['use_count'],
      maxUses: isset($row['max_uses']) ? (int) $row['max_uses'] : null,
      expiresAt: $row['expires_at'],
      lastUsedAt: $row['last_used_at'],
      revokedAt: $row['revoked_at'],
      revokedBy: isset($row['revoked_by']) ? (int) $row['revoked_by'] : null,
      ipAddress: $row['ip_address'],
      userAgent: $row['user_agent'],
      metadata: $row['metadata'],
      createdAt: $row['created_at'],
      updatedAt: $row['updated_at'],
    );
  }

  /**
   * Create token.
   */
  public function create(array $data): int {
    return $this->insert($data);
  }

  /**
   * Update token.
   */
  public function update(int $id, array $data): bool {
    return $this->updateById($id, $data);
  }

  /**
   * Delete token.
   */
  public function delete(int $id): bool {
    return $this->deleteById($id);
  }

  /**
   * Find by ID.
   */
  public function find(int $id): ?AuthToken {
    return $this->findById($id);
  }

  /**
   * Find by hash.
   */
  public function findByHash(string $hash): ?AuthToken {
    return $this->first([
      'token_hash' => $hash,
    ]);
  }

  /**
   * Get all tokens for a user.
   *
   * @return AuthToken[]
   */
  public function getByUser(int $userId): array {
    return $this->findWhere([
      'user_id' => $userId,
    ]);
  }

  /**
   * Get active tokens.
   *
   * @return AuthToken[]
   */
  public function getActiveByUser(int $userId): array {
    return $this->findWhere([
      'user_id' => $userId,
      'state'   => AuthTokenState::ACTIVE->value,
    ]);
  }
}
