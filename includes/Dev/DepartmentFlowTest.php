<?php

declare(strict_types=1);

namespace SupportBay\Dev;

use InvalidArgumentException;
use SupportBay\Core\Testing\Assert;
use SupportBay\Core\Testing\FlowTest;
use SupportBay\Modules\Departments\Enums\DepartmentStatus;
use SupportBay\Modules\Departments\Services\DepartmentService;
use SupportBay\Modules\Tickets\Enums\TicketPriority;

final class DepartmentFlowTest extends FlowTest {
  protected static function title(): string { return 'Department Flow Test'; }

  protected static function execute(...$services): void {
    /** @var DepartmentService $departments */
    [$departments] = $services;
    $default = $departments->ensureDefault();
    Assert::true(
      $default->name() === 'Support'
      && $default->slug() === DepartmentService::DEFAULT_SLUG
      && $default->status() === DepartmentStatus::ACTIVE,
      'The active Support fallback department always exists.',
    );

    $protected = false;
    try { $departments->delete($default->id()); } catch (InvalidArgumentException) { $protected = true; }
    Assert::true($protected, 'The Support fallback department cannot be deleted.');

    $suffix = strtolower(wp_generate_password(8, false, false));
    $id = $departments->create([
      'name' => 'Billing ' . $suffix,
      'slug' => 'ignored-manual-slug',
    ]);

    try {
      $department = $departments->find($id);
      Assert::true(
        $department !== null
        && $department->slug() === 'billing-' . $suffix
        && $department->defaultPriority() === TicketPriority::NORMAL
        && $department->sortOrder() === 0,
        'Departments generate backend slugs and apply ticket-routing defaults.',
      );
      $departments->update($id, ['status' => DepartmentStatus::INACTIVE->value]);
      Assert::true($departments->find($id)?->isInactive() === true, 'Additional departments may be disabled.');
    } finally {
      if ($departments->find($id)) { $departments->delete($id); }
    }
  }
}
