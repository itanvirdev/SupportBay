<?php

declare(strict_types=1);

namespace SupportBay\Dev;

use InvalidArgumentException;
use SupportBay\Core\Testing\Assert;
use SupportBay\Core\Testing\FlowTest;
use SupportBay\Modules\Tickets\Http\Controllers\TicketSlaPolicyController;
use SupportBay\Modules\Tickets\Services\TicketSlaPolicyService;
use WP_Error;

final class TicketSlaPolicyFlowTest extends FlowTest {
  protected static function title(): string { return 'Ticket SLA Policy Flow Test'; }

  protected static function execute(...$services): void {
    /** @var TicketSlaPolicyService $policies */
    /** @var TicketSlaPolicyController $controller */
    [$policies, $controller] = $services;
    $existing = get_option('sbay_ticket_sla_policy', null);
    delete_option('sbay_ticket_sla_policy');
    try {
      $defaults = $policies->get();
      Assert::true($defaults->enabled() && $defaults->firstResponseMinutes()['urgent'] === 60, 'Safe priority SLA defaults are available.');
      $updated = $policies->update(['first_response_minutes' => ['urgent' => 30]]);
      Assert::true($updated->firstResponseMinutes()['urgent'] === 30 && $updated->firstResponseMinutes()['normal'] === 1440, 'Partial SLA updates preserve omitted priorities.');
      try {
        $policies->update(['first_response_minutes' => ['urgent' => 5]]);
        Assert::true(false, 'Unsafe SLA targets must be rejected.');
      } catch (InvalidArgumentException) {
        Assert::true(true, 'Unsafe SLA targets are rejected.');
      }
      if (did_action('rest_api_init') === 0) { do_action('rest_api_init', rest_get_server()); }
      Assert::true(isset(rest_get_server()->get_routes()['/sbay/v1/admin/ticket-sla-policy']), 'Ticket SLA settings route is registered.');
      wp_set_current_user(0);
      Assert::true($controller->permissions() instanceof WP_Error, 'Anonymous SLA settings access is rejected.');
      wp_set_current_user(1);
      Assert::true($controller->permissions() === true, 'Administrators can manage SLA settings.');
    } finally {
      if ($existing === null) { delete_option('sbay_ticket_sla_policy'); }
      else { update_option('sbay_ticket_sla_policy', $existing, false); }
      wp_set_current_user(0);
    }
  }
}
