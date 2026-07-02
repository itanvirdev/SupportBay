<?php

declare(strict_types=1);

namespace SupportBay\Modules\Providers\Services;

use RuntimeException;
use SupportBay\Modules\Providers\Entities\Provider;
use SupportBay\Modules\Providers\Enums\ProviderCategory;
use SupportBay\Modules\Providers\Enums\ProviderStatus;
use SupportBay\Modules\Providers\Repositories\ProviderRepository;

final class ProviderService {
  /**
   * Repository.
   */
  public function __construct(
    private readonly ProviderRepository $repository,
  ) {
  }

  /**
   * Create provider.
   */
  public function create(array $data): int {
    $slug = trim($data['slug']);

    if ($this->repository->slugExists($slug)) {
      throw new RuntimeException(
        sprintf(
          'Provider "%s" already exists.',
          $slug
        )
      );
    }

    return $this->repository->create([
      'slug'              => $slug,
      'name'              => $data['name'],
      'category'          => ($data['category'] ?? ProviderCategory::OTHER) instanceof ProviderCategory
        ? $data['category']->value
        : ($data['category'] ?? ProviderCategory::OTHER->value),
      'version'           => $data['version'] ?? null,
      'status'            => ($data['status'] ?? ProviderStatus::DISABLED) instanceof ProviderStatus
        ? $data['status']->value
        : ($data['status'] ?? ProviderStatus::DISABLED->value),
      'settings'          => isset($data['settings'])
        ? wp_json_encode($data['settings'])
        : null,
      'last_connected_at' => null,
      'last_error'        => null,
      'metadata'          => isset($data['metadata'])
        ? wp_json_encode($data['metadata'])
        : null,
      'created_at'        => current_time('mysql'),
      'updated_at'        => current_time('mysql'),
    ]);
  }

  /**
   * Update provider.
   */
  public function update(
    int $id,
    array $data,
  ): bool {
    if (isset($data['category']) && $data['category'] instanceof ProviderCategory) {
      $data['category'] = $data['category']->value;
    }

    if (isset($data['status']) && $data['status'] instanceof ProviderStatus) {
      $data['status'] = $data['status']->value;
    }

    if (isset($data['settings']) && is_array($data['settings'])) {
      $data['settings'] = wp_json_encode($data['settings']);
    }

    if (isset($data['metadata']) && is_array($data['metadata'])) {
      $data['metadata'] = wp_json_encode($data['metadata']);
    }

    $data['updated_at'] = current_time('mysql');

    return $this->repository->update($id, $data);
  }

  /**
   * Delete provider.
   */
  public function delete(int $id): bool {
    return $this->repository->delete($id);
  }

  /**
   * Find provider.
   */
  public function find(int $id): ?Provider {
    return $this->repository->find($id);
  }

  /**
   * Find provider by slug.
   */
  public function findBySlug(string $slug): ?Provider {
    return $this->repository->findBySlug($slug);
  }

  /**
   * Get all providers.
   *
   * @return Provider[]
   */
  public function all(): array {
    return $this->repository->all();
  }

  /**
   * Get enabled providers.
   *
   * @return Provider[]
   */
  public function enabled(): array {
    return $this->repository->enabled();
  }

  /**
   * Enable provider.
   */
  public function enable(int $id): bool {
    return $this->repository->update($id, [
      'status'     => ProviderStatus::ENABLED->value,
      'updated_at' => current_time('mysql'),
    ]);
  }

  /**
   * Disable provider.
   */
  public function disable(int $id): bool {
    return $this->repository->update($id, [
      'status'     => ProviderStatus::DISABLED->value,
      'updated_at' => current_time('mysql'),
    ]);
  }

  /**
   * Update provider settings.
   */
  public function updateSettings(
    int $id,
    array $settings,
  ): bool {
    return $this->repository->update($id, [
      'settings'   => wp_json_encode($settings),
      'updated_at' => current_time('mysql'),
    ]);
  }

  /**
   * Record successful connection.
   */
  public function connected(int $id): bool {
    return $this->repository->update($id, [
      'last_connected_at' => current_time('mysql'),
      'last_error'        => null,
      'updated_at'        => current_time('mysql'),
    ]);
  }

  /**
   * Record connection failure.
   */
  public function connectionFailed(
    int $id,
    string $error,
  ): bool {
    return $this->repository->update($id, [
      'last_error' => $error,
      'updated_at' => current_time('mysql'),
    ]);
  }
}
