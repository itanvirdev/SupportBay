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
    $primary = $categories->create([
      'name'          => 'Technical Support ' . $suffix,
      'color'         => '#216e52',
    ]);
    $global = $categories->create([
      'name' => 'General Support ' . $suffix,
    ]);

    try {
      Assert::true(
        $primary->id() > 0 && $primary->isActive(),
        'A sanitized active category is created.'
      );

      $activeIds = array_map(static fn($item): int => $item->id(), $categories->active());
      Assert::true(
        in_array($primary->id(), $activeIds, true) && in_array($global->id(), $activeIds, true),
        'Active categories are globally available.'
      );

      Assert::equals($primary->id(), $categories->validateSelection($primary->id())?->id(), 'An active category is valid for ticket classification.');

      $updated = $categories->update($primary->id(), [
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
        $categories->validateSelection($primary->id());
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
      $categories->delete($primary->id());
      $categories->delete($global->id());
    }

    Assert::true(
      $categories->find($primary->id()) === null,
      'Category deletion is deterministic.'
    );
  }
}
