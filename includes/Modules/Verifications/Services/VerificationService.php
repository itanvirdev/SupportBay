<?php

declare(strict_types=1);

namespace SupportBay\Modules\Verifications\Services;

use RuntimeException;
use SupportBay\Core\Events\EventDispatcher;
use SupportBay\Core\Integrations\Contracts\PurchaseVerificationProvider;
use SupportBay\Core\Integrations\IntegrationManager;
use SupportBay\Modules\Verifications\Entities\Verification;
use SupportBay\Modules\Verifications\Enums\VerificationStatus;
use SupportBay\Modules\Verifications\Events\VerificationCreated;
use SupportBay\Modules\Verifications\Events\VerificationRefreshed;
use SupportBay\Modules\Verifications\Events\VerificationRevoked;
use SupportBay\Modules\Verifications\Events\VerificationVerified;
use SupportBay\Modules\Verifications\Repositories\VerificationRepository;

final class VerificationService {
  /**
   * Constructor.
   */
  public function __construct(
    private readonly VerificationRepository $repository,
    private readonly EventDispatcher $events,
    private readonly IntegrationManager $integrations,
  ) {
  }

  /**
   * Create a new verification record.
   */
  public function create(array $data): int {
    $provider = sanitize_key(
      (string) ($data['provider'] ?? '')
    );

    $reference = trim(
      (string) ($data['provider_reference'] ?? '')
    );

    if ($provider === '') {
      throw new RuntimeException(
        'Verification provider is required.'
      );
    }

    if ($reference === '') {
      throw new RuntimeException(
        'Provider reference is required.'
      );
    }

    if ($this->repository->findByReference($provider, $reference)) {
      throw new RuntimeException(
        sprintf(
          'Verification already exists for provider "%s".',
          $provider
        )
      );
    }

    $status = $this->resolveStatus(
      $data['verification_status']
        ?? VerificationStatus::PENDING
    );

    $now = current_time('mysql');

    $verificationId = $this->repository->create([
      'provider'                    => $provider,
      'provider_reference'          => $reference,
      'customer_id'                 => isset($data['customer_id'])
        ? (int) $data['customer_id']
        : null,
      'provider_customer_reference' => $this->nullableString(
        $data['provider_customer_reference'] ?? null
      ),
      'product_id'                  => $this->nullableString(
        $data['product_id'] ?? null
      ),
      'product_name'                => $this->nullableString(
        $data['product_name'] ?? null
      ),
      'license_type'                => $this->nullableString(
        $data['license_type'] ?? null
      ),
      'support_expires_at'          => $this->nullableString(
        $data['support_expires_at'] ?? null
      ),
      'purchased_at'                => $this->nullableString(
        $data['purchased_at'] ?? null
      ),
      'verified_at'                 => $status === VerificationStatus::VERIFIED
        ? ($data['verified_at'] ?? $now)
        : ($data['verified_at'] ?? null),
      'last_checked_at'             => $data['last_checked_at'] ?? null,
      'verification_status'         => $status->value,
      'provider_snapshot'           => $this->encodeJson(
        $data['provider_snapshot'] ?? null
      ),
      'metadata'                    => $this->encodeJson(
        $data['metadata'] ?? null
      ),
      'created_at'                  => $now,
      'updated_at'                  => $now,
    ]);

    $verification = $this->findOrFail($verificationId);

    $this->events->dispatch(
      new VerificationCreated($verification)
    );

    if ($verification->isVerified()) {
      $this->events->dispatch(
        new VerificationVerified($verification)
      );
    }

    return $verificationId;
  }

  /**
   * Update a verification record.
   */
  public function update(
    int $id,
    array $data,
  ): bool {
    $verification = $this->findOrFail($id);

    if (isset($data['provider'])) {
      $data['provider'] = sanitize_key(
        (string) $data['provider']
      );
    }

    if (isset($data['provider_reference'])) {
      $reference = trim(
        (string) $data['provider_reference']
      );

      if ($reference === '') {
        throw new RuntimeException(
          'Provider reference cannot be empty.'
        );
      }

      $existing = $this->repository->findByReference(
        $data['provider'] ?? $verification->provider(),
        $reference
      );

      if ($existing && $existing->id() !== $id) {
        throw new RuntimeException(
          'Another verification already uses this provider reference.'
        );
      }

      $data['provider_reference'] = $reference;
    }

    if (isset($data['verification_status'])) {
      $data['verification_status'] = $this
        ->resolveStatus($data['verification_status'])
        ->value;
    }

    if (
      array_key_exists('provider_snapshot', $data) &&
      is_array($data['provider_snapshot'])
    ) {
      $data['provider_snapshot'] = wp_json_encode(
        $data['provider_snapshot']
      );
    }

    if (
      array_key_exists('metadata', $data) &&
      is_array($data['metadata'])
    ) {
      $data['metadata'] = wp_json_encode(
        $data['metadata']
      );
    }

    $data['updated_at'] = current_time('mysql');

    return $this->repository->update($id, $data);
  }

  /**
   * Delete a verification.
   */
  public function delete(int $id): bool {
    $this->findOrFail($id);

    return $this->repository->delete($id);
  }

  /**
   * Find verification by ID.
   */
  public function find(int $id): ?Verification {
    /** @var Verification|null */
    return $this->repository->find($id);
  }

  /**
   * Find verification or throw an exception.
   */
  public function findOrFail(int $id): Verification {
    $verification = $this->find($id);

    if (! $verification) {
      throw new RuntimeException(
        sprintf(
          'Verification with ID %d was not found.',
          $id
        )
      );
    }

    return $verification;
  }

  /**
   * Find verification by provider reference.
   */
  public function findByReference(
    string $provider,
    string $reference,
  ): ?Verification {
    return $this->repository->findByReference(
      sanitize_key($provider),
      trim($reference)
    );
  }

  /**
   * Get customer verifications.
   *
   * @return Verification[]
   */
  public function findByCustomer(int $customerId): array {
    return $this->repository->findByCustomer($customerId);
  }

  /**
   * Get provider verifications.
   *
   * @return Verification[]
   */
  public function findByProvider(string $provider): array {
    return $this->repository->findByProvider(
      sanitize_key($provider)
    );
  }

  /**
   * Get verifications by status.
   *
   * @return Verification[]
   */
  public function findByStatus(
    VerificationStatus $status,
  ): array {
    return $this->repository->findByStatus($status);
  }

  /**
   * Verify a purchase through a registered integration.
   *
   * Repeated requests for the same provider reference return the
   * existing verification instead of creating duplicate records.
   *
   * @param array<string, mixed> $context
   */
  public function verifyPurchase(
    string $provider,
    string $reference,
    array $context = [],
    ?int $customerId = null,
  ): Verification {
    $provider = sanitize_key($provider);
    $reference = trim($reference);

    if ($provider === '') {
      throw new RuntimeException(
        'Verification provider is required.'
      );
    }

    if ($reference === '') {
      throw new RuntimeException(
        'Provider reference is required.'
      );
    }

    $existing = $this->repository->findByReference(
      $provider,
      $reference,
    );

    if ($existing) {
      return $existing;
    }

    $integration = $this->integrations->integration($provider);

    if (! $integration instanceof PurchaseVerificationProvider) {
      throw new RuntimeException(
        sprintf(
          'Integration "%s" does not support purchase verification.',
          $provider
        )
      );
    }

    $verificationData = $integration->verifyPurchase(
      $reference,
      $context,
    );

    if ($verificationData->provider() !== $provider) {
      throw new RuntimeException(
        'Purchase verification provider does not match the requested integration.'
      );
    }

    if ($verificationData->providerReference() !== $reference) {
      throw new RuntimeException(
        'Purchase verification reference does not match the requested reference.'
      );
    }

    $data = $verificationData->toArray();
    $data['customer_id'] = $customerId;
    $data['last_checked_at'] = current_time('mysql');

    $verificationId = $this->create($data);

    return $this->findOrFail($verificationId);
  }

  /**
   * Mark a verification as successfully verified.
   *
   * The supplied data represents the normalized provider result.
   */
  public function verify(
    int $id,
    array $data = [],
  ): Verification {
    $this->findOrFail($id);

    $now = current_time('mysql');

    $updates = [
      'verification_status' => VerificationStatus::VERIFIED->value,
      'verified_at'         => $data['verified_at'] ?? $now,
      'last_checked_at'     => $data['last_checked_at'] ?? $now,
      'updated_at'          => $now,
    ];

    $updates = $this->mergeProviderData(
      $updates,
      $data
    );

    $this->repository->update($id, $updates);

    $verification = $this->findOrFail($id);

    $this->events->dispatch(
      new VerificationVerified($verification)
    );

    return $verification;
  }

  /**
   * Refresh a verification using new provider data.
   */
  public function refresh(
    int $id,
    array $data,
  ): Verification {
    $current = $this->findOrFail($id);

    if (! $current->canRefresh()) {
      throw new RuntimeException(
        'This verification cannot be refreshed.'
      );
    }

    $now = current_time('mysql');

    $updates = [
      'last_checked_at' => $data['last_checked_at'] ?? $now,
      'updated_at'      => $now,
    ];

    if (isset($data['verification_status'])) {
      $updates['verification_status'] = $this
        ->resolveStatus($data['verification_status'])
        ->value;
    }

    $updates = $this->mergeProviderData(
      $updates,
      $data
    );

    $this->repository->update($id, $updates);

    $verification = $this->findOrFail($id);

    $this->events->dispatch(
      new VerificationRefreshed($verification)
    );

    return $verification;
  }

  /**
   * Mark verification support as expired.
   */
  public function expire(int $id): Verification {
    $this->findOrFail($id);

    $now = current_time('mysql');

    $this->repository->update($id, [
      'verification_status' => VerificationStatus::EXPIRED->value,
      'last_checked_at'     => $now,
      'updated_at'          => $now,
    ]);

    return $this->findOrFail($id);
  }

  /**
   * Mark a verification as invalid.
   */
  public function invalidate(
    int $id,
    ?array $snapshot = null,
  ): Verification {
    $this->findOrFail($id);

    $now = current_time('mysql');

    $data = [
      'verification_status' => VerificationStatus::INVALID->value,
      'last_checked_at'     => $now,
      'updated_at'          => $now,
    ];

    if ($snapshot !== null) {
      $data['provider_snapshot'] = wp_json_encode($snapshot);
    }

    $this->repository->update($id, $data);

    return $this->findOrFail($id);
  }

  /**
   * Revoke a verification.
   */
  public function revoke(int $id): Verification {
    $verification = $this->findOrFail($id);

    if ($verification->isRevoked()) {
      return $verification;
    }

    $now = current_time('mysql');

    $this->repository->update($id, [
      'verification_status' => VerificationStatus::REVOKED->value,
      'last_checked_at'     => $now,
      'updated_at'          => $now,
    ]);

    $verification = $this->findOrFail($id);

    $this->events->dispatch(
      new VerificationRevoked($verification)
    );

    return $verification;
  }

  /**
   * Merge normalized provider data into update values.
   */
  private function mergeProviderData(
    array $updates,
    array $data,
  ): array {
    $fields = [
      'customer_id',
      'provider_customer_reference',
      'product_id',
      'product_name',
      'license_type',
      'support_expires_at',
      'purchased_at',
    ];

    foreach ($fields as $field) {
      if (array_key_exists($field, $data)) {
        $updates[$field] = $data[$field];
      }
    }

    if (array_key_exists('provider_snapshot', $data)) {
      $updates['provider_snapshot'] = $this->encodeJson(
        $data['provider_snapshot']
      );
    }

    if (array_key_exists('metadata', $data)) {
      $updates['metadata'] = $this->encodeJson(
        $data['metadata']
      );
    }

    return $updates;
  }

  /**
   * Resolve verification status.
   */
  private function resolveStatus(
    VerificationStatus|string $status,
  ): VerificationStatus {
    if ($status instanceof VerificationStatus) {
      return $status;
    }

    return VerificationStatus::from($status);
  }

  /**
   * Convert empty values to null.
   */
  private function nullableString(mixed $value): ?string {
    if ($value === null) {
      return null;
    }

    $value = trim((string) $value);

    return $value !== ''
      ? $value
      : null;
  }

  /**
   * Encode JSON database fields.
   */
  private function encodeJson(mixed $value): ?string {
    if ($value === null || $value === '') {
      return null;
    }

    if (is_string($value)) {
      return $value;
    }

    $encoded = wp_json_encode($value);

    if ($encoded === false) {
      throw new RuntimeException(
        'Unable to encode verification data.'
      );
    }

    return $encoded;
  }
}
