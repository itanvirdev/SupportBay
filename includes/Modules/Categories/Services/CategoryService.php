<?php

declare(strict_types=1);

namespace SupportBay\Modules\Categories\Services;

use InvalidArgumentException;
use SupportBay\Modules\Categories\Entities\Category;
use SupportBay\Modules\Categories\Enums\CategoryStatus;
use SupportBay\Modules\Categories\Repositories\CategoryRepository;
use SupportBay\Modules\Tickets\Repositories\TicketRepository;

final class CategoryService {
  public function __construct(
    private readonly CategoryRepository $repository,
    private readonly TicketRepository $tickets,
  ) {
  }

  /** @param array<string, mixed> $data */
  public function create(array $data): Category {
    $normalized = $this->normalize($data, true);

    if ($this->repository->findBySlug($normalized['slug'])) {
      throw new InvalidArgumentException(
        'Category slug already exists.'
      );
    }

    $id = $this->repository->create($normalized);

    return $this->repository->find($id)
      ?? throw new InvalidArgumentException(
        'Category could not be created.'
      );
  }

  /** @param array<string, mixed> $data */
  public function update(int $id, array $data): ?Category {
    if (! $this->repository->find($id)) {
      return null;
    }

    $normalized = $this->normalize($data, false);

    if (isset($normalized['slug'])) {
      $match = $this->repository->findBySlug($normalized['slug']);

      if ($match && $match->id() !== $id) {
        throw new InvalidArgumentException(
          'Category slug already exists.'
        );
      }
    }

    if ($normalized !== []) {
      $this->repository->update($id, $normalized);
    }

    return $this->repository->find($id);
  }

  public function find(int $id): ?Category {
    return $this->repository->find($id);
  }

  /** @return Category[] */
  public function all(): array {
    return $this->repository->all();
  }

  /** @return Category[] */
  public function active(): array {
    return $this->repository->active();
  }

  public function delete(int $id): bool {
    if (! $this->repository->find($id)) {
      return false;
    }

    if ($this->tickets->countByCategory($id) > 0) {
      throw new InvalidArgumentException(
        'Categories used by tickets must be deactivated instead of deleted.'
      );
    }

    return $this->repository->delete($id);
  }

  /** @return Category[] */
  public function applicable(int $departmentId): array {
    return array_values(array_filter(
      $this->active(),
      static fn(Category $category): bool =>
        $category->departmentId() === null
        || $category->departmentId() === $departmentId,
    ));
  }

  public function validateSelection(
    ?int $categoryId,
    int $departmentId,
  ): ?Category {
    if ($categoryId === null) {
      return null;
    }

    $category = $this->find($categoryId);

    if (
      ! $category
      || ! $category->isActive()
      || (
        $category->departmentId() !== null
        && $category->departmentId() !== $departmentId
      )
    ) {
      throw new InvalidArgumentException(
        'Please select an available category.'
      );
    }

    return $category;
  }

  /**
   * @param array<string, mixed> $data
   * @return array<string, mixed>
   */
  private function normalize(array $data, bool $creating): array {
    $result = [];

    if ($creating || array_key_exists('name', $data)) {
      $name = sanitize_text_field((string) ($data['name'] ?? ''));

      if ($name === '') {
        throw new InvalidArgumentException(
          'Category name is required.'
        );
      }

      $result['name'] = $name;

      if ($creating && ! isset($data['slug'])) {
        $data['slug'] = $name;
      }
    }

    if ($creating || array_key_exists('slug', $data)) {
      $slug = sanitize_title((string) ($data['slug'] ?? ''));

      if ($slug === '') {
        throw new InvalidArgumentException(
          'Category slug is required.'
        );
      }

      $result['slug'] = $slug;
    }

    if ($creating || array_key_exists('description', $data)) {
      $result['description'] = sanitize_textarea_field(
        (string) ($data['description'] ?? '')
      ) ?: null;
    }

    if ($creating || array_key_exists('department_id', $data)) {
      $result['department_id'] = absint(
        $data['department_id'] ?? 0
      ) ?: null;
    }

    if ($creating || array_key_exists('status', $data)) {
      $status = CategoryStatus::tryFrom(sanitize_key(
        (string) ($data['status'] ?? CategoryStatus::ACTIVE->value)
      ));

      if (! $status) {
        throw new InvalidArgumentException(
          'Category status is invalid.'
        );
      }

      $result['status'] = $status->value;
    }

    if ($creating || array_key_exists('color', $data)) {
      $color = sanitize_hex_color((string) ($data['color'] ?? ''));
      $result['color'] = $color ?: null;
    }

    if ($creating || array_key_exists('sort_order', $data)) {
      $result['sort_order'] = absint($data['sort_order'] ?? 0);
    }

    return $result;
  }
}
