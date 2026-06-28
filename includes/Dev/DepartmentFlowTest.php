<?php

declare(strict_types=1);

namespace SupportBay\Dev;

use SupportBay\Core\Testing\Assert;
use SupportBay\Core\Testing\FlowTest;
use SupportBay\Modules\Departments\Enums\DepartmentStatus;
use SupportBay\Modules\Tickets\Enums\TicketPriority;
use SupportBay\Modules\Departments\Services\DepartmentService;

final class DepartmentFlowTest extends FlowTest {
  /**
   * Test title.
   */
  protected static function title(): string {
    return 'Department Flow Test';
  }

  /**
   * Execute flow.
   */
  protected static function execute(...$services): void {
    /** @var DepartmentService $departmentService */
    [$departmentService] = $services;

    echo "🚀 Starting SupportBay Department Flow Test...\n\n";

    // -------------------------------------------------
    // Create Department
    // -------------------------------------------------

    $departmentId = $departmentService->create([
      'name' => 'Support',
      'slug' => 'support',
    ]);

    Assert::true(
      $departmentId > 0,
      'Department created.'
    );

    // -------------------------------------------------
    // Retrieve Department
    // -------------------------------------------------

    $department = $departmentService->find($departmentId);

    Assert::notNull(
      $department,
      'Department retrieved.'
    );

    Assert::equals(
      $departmentId,
      $department->id(),
      'Department ID matches.'
    );

    Assert::equals(
      'Support',
      $department->name(),
      'Department name stored.'
    );

    Assert::equals(
      'support',
      $department->slug(),
      'Department slug stored.'
    );

    Assert::equals(
      DepartmentStatus::ACTIVE,
      $department->status(),
      'Default status applied.'
    );

    Assert::equals(
      TicketPriority::NORMAL,
      $department->defaultPriority(),
      'Default priority applied.'
    );

    Assert::equals(
      0,
      $department->sortOrder(),
      'Default sort order applied.'
    );
  }
}
