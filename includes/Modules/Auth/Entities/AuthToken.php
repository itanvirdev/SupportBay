<?php

declare(strict_types=1);

namespace SupportBay\Modules\Auth\Entities;

use SupportBay\Modules\Auth\Enums\AuthTokenState;
use SupportBay\Modules\Auth\Enums\AuthTokenType;

final class AuthToken {
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
   * Current usage count.
   */
  public function useCount(): int {
    return $this->useCount;
  }

  /**
   * Maximum allowed uses.
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
   * Last successful use.
   */
  public function lastUsedAt(): ?string {
    return $this->lastUsedAt;
  }

  /**
   * Revocation timestamp.
   */
  public function revokedAt(): ?string {
    return $this->revokedAt;
  }

  /**
   * User who revoked token.
   */
  public function revokedBy(): ?int {
    return $this->revokedBy;
  }

  /**
   * Creator IP address.
   */
  public function ipAddress(): ?string {
    return $this->ipAddress;
  }

  /**
   * Browser / device information.
   */
  public function userAgent(): ?string {
    return $this->userAgent;
  }

  /**
   * JSON metadata.
   */
  public function metadata(): ?string {
    return $this->metadata;
  }

  /**
   * Creation timestamp.
   */
  public function createdAt(): string {
    return $this->createdAt;
  }

  /**
   * Last update timestamp.
   */
  public function updatedAt(): string {
    return $this->updatedAt;
  }
}
