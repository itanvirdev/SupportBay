<?php

declare(strict_types=1);

namespace SupportBay\Modules\Auth\Entities;

use SupportBay\Core\Entities\Entity;
use SupportBay\Modules\Auth\Enums\AuthTokenState;
use SupportBay\Modules\Auth\Enums\AuthTokenType;

final class AuthToken extends Entity {
  public function __construct(
    private int $id,
    private int $userId,
    private AuthTokenType $type,
    private AuthTokenState $state,
    private string $tokenHash,
    private ?string $redirectTo,
    private int $useCount,
    private ?int $maxUses,
    private string $expiresAt,
    private ?string $lastUsedAt,
    private ?string $revokedAt,
    private ?int $revokedBy,
    private ?string $ipAddress,
    private ?string $userAgent,
    private ?string $metadata,
    private string $createdAt,
    private string $updatedAt,
  ) {
  }

  /**
   * Convert entity to array.
   */
  public function toArray(): array {
    return [
      'id'           => $this->id,
      'user_id'      => $this->userId,
      'type'         => $this->type->value,
      'state'        => $this->state->value,
      'token_hash'   => $this->tokenHash,
      'redirect_to'  => $this->redirectTo,
      'use_count'    => $this->useCount,
      'max_uses'     => $this->maxUses,
      'expires_at'   => $this->expiresAt,
      'last_used_at' => $this->lastUsedAt,
      'revoked_at'   => $this->revokedAt,
      'revoked_by'   => $this->revokedBy,
      'ip_address'   => $this->ipAddress,
      'user_agent'   => $this->userAgent,
      'metadata'     => $this->metadata,
      'created_at'   => $this->createdAt,
      'updated_at'   => $this->updatedAt,
    ];
  }

  // -----------------------------------------------------------------
  // Getters
  // -----------------------------------------------------------------

  /**
   * Token ID.
   */
  public function id(): int {
    return $this->id;
  }

  /**
   * WordPress user ID.
   */
  public function userId(): int {
    return $this->userId;
  }

  /**
   * Token type.
   */
  public function type(): AuthTokenType {
    return $this->type;
  }

  /**
   * Token state.
   */
  public function state(): AuthTokenState {
    return $this->state;
  }

  /**
   * SHA-256 token hash.
   */
  public function tokenHash(): string {
    return $this->tokenHash;
  }

  /**
   * Redirect destination.
   */
  public function redirectTo(): ?string {
    return $this->redirectTo;
  }

  /**
   * Usage count.
   */
  public function useCount(): int {
    return $this->useCount;
  }

  /**
   * Maximum uses.
   *
   * Null means unlimited.
   */
  public function maxUses(): ?int {
    return $this->maxUses;
  }

  /**
   * Expiration timestamp.
   */
  public function expiresAt(): string {
    return $this->expiresAt;
  }

  /**
   * Last successful authentication.
   */
  public function lastUsedAt(): ?string {
    return $this->lastUsedAt;
  }

  /**
   * Revoked timestamp.
   */
  public function revokedAt(): ?string {
    return $this->revokedAt;
  }

  /**
   * Revoked by user.
   */
  public function revokedBy(): ?int {
    return $this->revokedBy;
  }

  /**
   * IP address.
   */
  public function ipAddress(): ?string {
    return $this->ipAddress;
  }

  /**
   * Browser / device.
   */
  public function userAgent(): ?string {
    return $this->userAgent;
  }

  /**
   * Metadata.
   */
  public function metadata(): ?string {
    return $this->metadata;
  }

  /**
   * Created timestamp.
   */
  public function createdAt(): string {
    return $this->createdAt;
  }

  /**
   * Updated timestamp.
   */
  public function updatedAt(): string {
    return $this->updatedAt;
  }

  /*
  |--------------------------------------------------------------------------
  | Domain Methods
  |--------------------------------------------------------------------------
  */

  /**
   * Is token active?
   */
  public function isActive(): bool {
    return $this->state === AuthTokenState::ACTIVE;
  }

  /**
   * Is token revoked?
   */
  public function isRevoked(): bool {
    return $this->state === AuthTokenState::REVOKED;
  }

  /**
   * Has token expired?
   */
  public function isExpired(): bool {
    return strtotime($this->expiresAt) <= time();
  }

  /**
   * Has token ever been used?
   */
  public function hasBeenUsed(): bool {
    return $this->useCount > 0;
  }

  /**
   * Has redirect destination?
   */
  public function hasRedirect(): bool {
    return ! empty($this->redirectTo);
  }

  /**
   * Unlimited usage?
   */
  public function isUnlimited(): bool {
    return $this->maxUses === null;
  }

  /**
   * Remaining uses.
   *
   * Null means unlimited.
   */
  public function remainingUses(): ?int {
    if ($this->isUnlimited()) {
      return null;
    }

    return max(0, $this->maxUses - $this->useCount);
  }

  /**
   * Has remaining uses?
   */
  public function hasRemainingUses(): bool {
    return $this->isUnlimited() || $this->remainingUses() > 0;
  }

  /**
   * Can token be used?
   */
  public function canBeUsed(): bool {
    return $this->isActive()
      && ! $this->isExpired()
      && $this->hasRemainingUses();
  }
}
