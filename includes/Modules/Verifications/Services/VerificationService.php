<?php

declare(strict_types=1);

namespace SupportBay\Modules\Verifications;

use RuntimeException;
use SupportBay\Core\Integrations\IntegrationManager;
use SupportBay\Modules\Verifications\Repositories\VerificationRepository;
use SupportBay\Modules\Verifications\Entities\Verification;

final class VerificationService {
  public function __construct(
    private readonly VerificationRepository $repository,
    private readonly IntegrationManager $integrations,
  ) {
  }

  /**
   * Find verification by ID.
   */
  public function find(int $id): ?Verification {
    /** @var Verification|null */
    return $this->repository->find($id);
  }

  /**
   * Find by provider reference.
   */
  public function findByReference(
    string $provider,
    string $reference,
  ): ?Verification {
    return $this->repository->findByReference(
      $provider,
      $reference,
    );
  }

  /**
   * Store a verification.
   */
  public function store(Verification $verification): int {
    /** @var int */
    return $this->repository->create(
      $verification->toArray(),
    );
  }

  /**
   * Update a verification.
   */
  public function update(Verification $verification): bool {
    return $this->repository->update(
      $verification->id(),
      $verification->toArray(),
    );
  }

  /**
   * Delete a verification.
   */
  public function delete(int $id): bool {
    return $this->repository->delete($id);
  }

  /**
   * Verify a provider reference.
   *
   * Business workflow only.
   * Provider-specific API calls belong to the Integration Provider.
   */
  public function verify(
    string $provider,
    string $reference,
  ): Verification {

    // implemented later

    throw new RuntimeException(
      'Verification workflow not implemented.'
    );
  }

  /**
   * Refresh an existing verification.
   */
  public function refresh(
    Verification $verification,
  ): Verification {

    // implemented later

    throw new RuntimeException(
      'Refresh workflow not implemented.'
    );
  }

  /**
   * Revoke a verification.
   */
  public function revoke(
    Verification $verification,
  ): bool {

    // implemented later

    throw new RuntimeException(
      'Revoke workflow not implemented.'
    );
  }
}
