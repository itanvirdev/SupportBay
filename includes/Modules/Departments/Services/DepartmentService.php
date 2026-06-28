<?php

declare(strict_types=1);

namespace SupportBay\Modules\Departments\Services;

use InvalidArgumentException;
use RuntimeException;
use SupportBay\Modules\Departments\Entities\Department;
use SupportBay\Modules\Departments\Enums\DepartmentStatus;
use SupportBay\Modules\Departments\Repositories\DepartmentRepository;
use SupportBay\Modules\Tickets\Enums\TicketPriority;

final class DepartmentService {

  public function __construct(
    private DepartmentRepository $repository
  ) {
  }

  /**
   * Create department
   */
  public function create(array $data): int {
    $this->validate($data);

    $data = $this->normalizeForCreate($data);

    return $this->repository->create($data);
  }

  /**
   * Update department
   */
  public function update(int $id, array $data): ?Department {
    $department = $this->repository->find($id);

    if (!$department) {
      return null;
    }

    $data = $this->normalizeForUpdate($data);

    $this->repository->update($id, $data);

    return $this->repository->find($id);
  }

  /**
   * Find department
   */
  public function find(int $id): ?Department {
    return $this->repository->find($id);
  }

  /**
   * Find by slug
   */
  public function findBySlug(string $slug): ?Department {
    return $this->repository->findBySlug($slug);
  }

  /**
   * List all departments
   *
   * @return Department[]
   */
  public function all(): array {
    return $this->repository->getAll();
  }

  /**
   * List active departments
   *
   * @return Department[]
   */
  public function active(): array {
    return $this->repository->getActive();
  }

  /**
   * Delete department
   */
  public function delete(int $id): bool {
    $department = $this->repository->find($id);

    if (!$department) {
      return false;
    }

    /**
     * Reserved:
     * Cannot delete default department.
     */

    /**
     * Reserved:
     * Cannot delete department if tickets exist.
     */

    return $this->repository->delete($id);
  }

  /**
   * Validate incoming data
   */
  private function validate(array $data): void {
    if (empty($data['name'])) {
      throw new InvalidArgumentException('Department name is required.');
    }
  }

  /**
   * Normalize defaults for create
   */
  private function normalizeForCreate(array $data): array {
    $data['slug'] ??= $this->generateSlug($data['name']);
    $data['status'] ??= DepartmentStatus::default()->value;
    $data['default_priority'] ??= TicketPriority::NORMAL->value;
    $data['sort_order'] ??= 0;

    return $data;
  }

  /**
   * Normalize defaults for update
   */
  private function normalizeForUpdate(array $data): array {
    if (isset($data['name']) && empty($data['slug'])) {
      $data['slug'] = $this->generateSlug($data['name']);
    }

    return $data;
  }

  /**
   * Generate slug
   */
  private function generateSlug(string $name): string {
    return sanitize_title($name);
  }
}
