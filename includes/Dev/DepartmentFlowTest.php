<?php

declare(strict_types=1);

namespace SupportBay\Dev;

use SupportBay\Modules\Departments\Services\DepartmentService;
use SupportBay\Modules\Departments\Enums\DepartmentStatus;
use SupportBay\Modules\Tickets\Enums\TicketPriority;

final class DepartmentFlowTest {

  public static function run(
    DepartmentService $departmentService
  ): void {

    echo "<pre>";

    echo "🚀 Starting SupportBay Department Flow Test...\n\n";

    /**
     * -------------------------------------------------
     * Create Department
     * -------------------------------------------------
     */

    $department = $departmentService->create([
      'name' => 'Technical Support',
    ]);

    echo "✅ Department Created\n";
    echo "   ID: {$department->id()}\n";
    echo "   Name: {$department->name()}\n";
    echo "   Slug: {$department->slug()}\n";
    echo "   Status: {$department->status()->value}\n";
    echo "   Priority: {$department->defaultPriority()->value}\n\n";

    /**
     * -------------------------------------------------
     * Find by ID
     * -------------------------------------------------
     */

    $found = $departmentService->find($department->id());

    echo "🔍 Find By ID\n";
    echo "   Name: {$found->name()}\n";
    echo "   Active: " . ($found->isActive() ? 'YES' : 'NO') . "\n\n";

    /**
     * -------------------------------------------------
     * Find by Slug
     * -------------------------------------------------
     */

    $slug = $departmentService->findBySlug($department->slug());

    echo "🔍 Find By Slug\n";
    echo "   {$slug->slug()}\n\n";

    /**
     * -------------------------------------------------
     * Update
     * -------------------------------------------------
     */

    $updated = $departmentService->update(
      $department->id(),
      [
        'description' => 'Handles all technical issues.',
        'status' => DepartmentStatus::INACTIVE->value,
        'default_priority' => TicketPriority::HIGH->value,
      ]
    );

    echo "✏️ Department Updated\n";
    echo "   Status: {$updated->status()->value}\n";
    echo "   Priority: {$updated->defaultPriority()->value}\n";
    echo "   Active: " . ($updated->isActive() ? 'YES' : 'NO') . "\n\n";

    /**
     * -------------------------------------------------
     * Active Departments
     * -------------------------------------------------
     */

    $active = $departmentService->active();

    echo "📋 Active Departments\n";
    echo "   Count: " . count($active) . "\n\n";

    /**
     * -------------------------------------------------
     * Delete
     * -------------------------------------------------
     */

    $deleted = $departmentService->delete($department->id());

    echo "🗑 Department Deleted: ";
    echo $deleted ? "YES\n\n" : "NO\n\n";

    /**
     * -------------------------------------------------
     * Verify Delete
     * -------------------------------------------------
     */

    $verify = $departmentService->find($department->id());

    echo "🔍 Verify Delete: ";
    echo $verify ? "FAILED\n\n" : "SUCCESS\n\n";

    echo "🎯 Department Flow Completed Successfully.\n";

    echo "</pre>";
  }
}
