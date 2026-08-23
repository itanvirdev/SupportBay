<?php

declare(strict_types=1);

namespace SupportBay\Dev;

use SupportBay\Core\Database\DatabaseInstaller;
use SupportBay\Core\Testing\Assert;
use SupportBay\Core\Testing\FlowTest;
use SupportBay\Modules\AssignRules\Http\Controllers\AssignRuleController;
use SupportBay\Modules\AssignRules\Services\AssignRuleService;
use SupportBay\Modules\Categories\Services\CategoryService;
use WP_Error;

final class AssignRuleFlowTest extends FlowTest {
  protected static function title(): string { return 'Assign Rule Flow Test'; }
  protected static function execute(...$services): void {
    /** @var AssignRuleService $rules */
    /** @var CategoryService $categories */
    /** @var AssignRuleController $controller */
    [$rules, $categories, $controller] = $services;
    DatabaseInstaller::install();
    $suffix = strtolower(wp_generate_password(8, false, false));
    $category = $categories->create(['name' => 'Routing ' . $suffix]);
    $rule = null;
    $allCategoryRule = null;
    $previousDefaults = get_option('sbay_assign_rule_defaults_installed', null);
    $existingIds = array_map(static fn($item): int => $item->id(), $rules->all());
    try {
      $rule = $rules->create(['rule_type' => 'role', 'target_role' => 'administrator', 'category_ids' => [$category->id()], 'status' => 'active']);
      Assert::true($rule->isActive() && $rule->matchesCategory($category->id()), 'An active role assignment rule stores its category scope.');
      Assert::true($rules->options()['categories'] !== [] && $rules->options()['roles'] !== [], 'Rule options expose active categories and eligible support roles.');
      $rules->bulk([$rule->id()], 'deactivate');
      Assert::true($rules->find($rule->id())?->isActive() === false, 'Bulk deactivation updates selected rules.');
      $allCategoryRule = $rules->create(['rule_type' => 'role', 'target_role' => 'administrator', 'all_categories' => true, 'status' => 'active']);
      Assert::true($allCategoryRule->appliesToAllCategories() && $allCategoryRule->matchesCategory(null), 'All Categories rules match tickets even when no category exists.');
      delete_option('sbay_assign_rule_defaults_installed');
      $rules->provisionDefaults();
      foreach (['sbay_agent', 'sbay_manager'] as $role) {
        Assert::true((bool) array_filter($rules->all(), static fn($item): bool => $item->targetRole() === $role && $item->isActive() && $item->appliesToAllCategories()), sprintf('The active %s All Categories default is provisioned.', $role));
      }
      wp_set_current_user(0);
      Assert::true($controller->permissions() instanceof WP_Error, 'Anonymous assign-rule management is rejected.');
      wp_set_current_user(1);
      Assert::true($controller->permissions() === true, 'Administrators can manage assign rules.');
    } finally {
      if ($rule && $rules->find($rule->id())) { $rules->delete($rule->id()); }
      if ($allCategoryRule && $rules->find($allCategoryRule->id())) { $rules->delete($allCategoryRule->id()); }
      foreach ($rules->all() as $item) {
        if (! in_array($item->id(), $existingIds, true)) { $rules->delete($item->id()); }
      }
      if ($previousDefaults === null) { delete_option('sbay_assign_rule_defaults_installed'); }
      else { update_option('sbay_assign_rule_defaults_installed', $previousDefaults, false); }
      $categories->delete($category->id());
      wp_set_current_user(0);
    }
  }
}
