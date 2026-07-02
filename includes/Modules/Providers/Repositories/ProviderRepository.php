<?php

declare(strict_types=1);

namespace SupportBay\Modules\Providers\Repositories;

use SupportBay\Core\Database\Repository;
use SupportBay\Modules\Providers\Entities\Provider;
use SupportBay\Modules\Providers\Enums\ProviderCategory;
use SupportBay\Modules\Providers\Enums\ProviderStatus;

final class ProviderRepository extends Repository {
  /**
   * Database table.
   */
  protected function table(): string {
    return $this->db->prefix . 'sbay_providers';
  }

  /**
   * Hydrate entity.
   */
  protected function hydrate(array $row): Provider {
    return new Provider(
      id: (int) $row['id'],
      slug: $row['slug'],
      name: $row['name'],
      category: ProviderCategory::from($row['category']),
      version: $row['version'],
      status: ProviderStatus::from($row['status']),
      settings: ! empty($row['settings'])
        ? json_decode($row['settings'], true)
        : null,
      lastConnectedAt: $row['last_connected_at'],
      lastError: $row['last_error'],
      metadata: ! empty($row['metadata'])
        ? json_decode($row['metadata'], true)
        : null,
      createdAt: $row['created_at'],
      updatedAt: $row['updated_at'],
    );
  }

  /**
   * Find provider by slug.
   */
  public function findBySlug(string $slug): ?Provider {
    return $this->first([
      'slug' => $slug,
    ]);
  }

  /**
   * Find providers by category.
   *
   * @return Provider[]
   */
  public function findByCategory(
    ProviderCategory $category,
  ): array {
    return $this->findWhere([
      'category' => $category->value,
    ]);
  }

  /**
   * Find enabled providers.
   *
   * @return Provider[]
   */
  public function enabled(): array {
    return $this->findWhere([
      'status' => ProviderStatus::ENABLED->value,
    ]);
  }

  /**
   * Determine whether a slug already exists.
   */
  public function slugExists(string $slug): bool {
    return $this->exists([
      'slug' => $slug,
    ]);
  }
}
