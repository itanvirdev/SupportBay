<?php

declare(strict_types=1);

namespace SupportBay\Dev;

use InvalidArgumentException;
use SupportBay\Core\Testing\Assert;
use SupportBay\Core\Testing\FlowTest;
use SupportBay\Modules\Roles\Http\Controllers\SupportRoleController;
use SupportBay\Modules\Roles\Services\SupportRoleService;
use WP_Error;

final class RoleFlowTest extends FlowTest {
  protected static function title(): string { return 'Support Role Flow Test'; }

  protected static function execute(...$services): void {
    /** @var SupportRoleService $roles */
    /** @var SupportRoleController $controller */
    [$roles, $controller] = $services;
    require_once ABSPATH . 'wp-admin/includes/user.php';
    if (did_action('rest_api_init') === 0) { do_action('rest_api_init', rest_get_server()); }
    Assert::true(isset(rest_get_server()->get_routes()['/sbay/v1/roles']), 'Protected SupportBay role routes are registered.');
    wp_set_current_user(0);
    Assert::true($controller->canManage() instanceof WP_Error, 'Anonymous role management is rejected.');
    wp_set_current_user(1);
    Assert::true($controller->canManage() === true, 'Administrators can manage SupportBay roles.');
    $administrator = $roles->find('administrator');
    Assert::true($administrator !== null && ! $administrator->isEditable(), 'The native Administrator role is view-only.');

    $suffix = strtolower(wp_generate_password(8, false, false));
    $created = $roles->create([
      'name' => 'Escalation Specialist ' . $suffix,
      'description' => '<b>Handles escalations</b>',
      'capabilities' => ['sbay_view_tickets', 'sbay_reply_ticket', 'manage_options'],
    ]);
    $userId = 0;
    try {
      Assert::true(
        $created->slug() === 'sbay_escalation-specialist-' . $suffix
        && in_array('sbay_view_tickets', $created->capabilities(), true)
        && ! in_array('manage_options', $created->capabilities(), true)
        && get_role($created->slug())?->has_cap('sbay_access_dashboard') === true,
        'Role slugs are generated from names and roles persist only catalogued SupportBay capabilities with required dashboard access.',
      );
      $inactive = $roles->update($created->slug(), ['status' => 'inactive']);
      Assert::true(! $inactive->isActive() && get_role($created->slug())?->has_cap('sbay_view_tickets') === false, 'Inactive roles preserve definitions while removing support access.');
      $ordinary = $roles->update($created->slug(), ['status' => 'active', 'support_role' => false]);
      Assert::true(! $ordinary->isSupportRole() && $ordinary->capabilities() === [] && get_role($created->slug())?->has_cap('sbay_access_dashboard') === false, 'Disabling the support-role switch removes SupportBay access and capabilities.');
      $roles->update($created->slug(), ['support_role' => true, 'capabilities' => ['sbay_reply_ticket']]);
      $userId = wp_insert_user(['user_login'=>'sbay-role-'.$suffix,'user_pass'=>wp_generate_password(24),'role'=>$created->slug()]);
      $blocked = false;
      try { $roles->delete($created->slug()); } catch (InvalidArgumentException) { $blocked = true; }
      Assert::true($blocked, 'Roles assigned to WordPress users cannot be deleted.');
    } finally {
      if (is_int($userId) && $userId > 0) { wp_delete_user($userId); }
      if ($roles->find($created->slug())) { $roles->delete($created->slug()); }
      wp_set_current_user(0);
    }
  }
}
