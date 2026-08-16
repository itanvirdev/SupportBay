<?php

declare(strict_types=1);

namespace SupportBay\Dev;

use InvalidArgumentException;
use SupportBay\Core\Database\DatabaseInstaller;
use SupportBay\Core\Testing\Assert;
use SupportBay\Core\Testing\FlowTest;
use SupportBay\Modules\Categories\Enums\CategoryStatus;
use SupportBay\Modules\Categories\Services\CategoryService;

final class CategoryFlowTest extends FlowTest {
  protected static function title(): string {
    return 'Category Flow Test';
  }

  protected static function execute(...$services): void {
    /** @var CategoryService $categories */
    [$categories] = $services;

    DatabaseInstaller::install();
    $suffix = strtolower(wp_generate_password(8, false, false));
    $scoped = $categories->create([
      'name'          => 'Technical Support ' . $suffix,
      'department_id' => 2,
      'color'         => '#216e52',
    ]);
    $global = $categories->create([
      'name' => 'General Support ' . $suffix,
    ]);

    try {
      Assert::true(
        $scoped->id() > 0
        && $scoped->isActive()
        && $scoped->departmentId() === 2,
        'A sanitized active department-scoped category is created.'
      );

      Assert::count(
        2,
        $categories->applicable(2),
        'A department receives its scoped and global categories.'
      );

      Assert::count(
        1,
        $categories->applicable(3),
        'A different department receives only global categories.'
      );

      $rejected = false;

      try {
        $categories->validateSelection($scoped->id(), 3);
      } catch (InvalidArgumentException) {
        $rejected = true;
      }

      Assert::true(
        $rejected,
        'A category scoped to another department is rejected.'
      );

      $updated = $categories->update($scoped->id(), [
        'name'   => 'Product Support ' . $suffix,
        'status' => CategoryStatus::INACTIVE->value,
      ]);

      Assert::true(
        $updated !== null
        && ! $updated->isActive(),
        'Category lifecycle updates preserve identity.'
      );

      $rejected = false;

      try {
        $categories->validateSelection($scoped->id(), 2);
      } catch (InvalidArgumentException) {
        $rejected = true;
      }

      Assert::true(
        $rejected,
        'Inactive categories cannot be selected for new tickets.'
      );

      $duplicate = false;

      try {
        $categories->create([
          'name' => 'Another ' . $suffix,
          'slug' => $global->slug(),
        ]);
      } catch (InvalidArgumentException) {
        $duplicate = true;
      }

      Assert::true(
        $duplicate,
        'Duplicate category slugs are rejected.'
      );
    } finally {
      $categories->delete($scoped->id());
      $categories->delete($global->id());
    }

    Assert::true(
      $categories->find($scoped->id()) === null,
      'Category deletion is deterministic.'
    );
  }
}
