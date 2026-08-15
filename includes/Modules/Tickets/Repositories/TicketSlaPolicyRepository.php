<?php

declare(strict_types=1);

namespace SupportBay\Modules\Tickets\Repositories;

use SupportBay\Modules\Tickets\Entities\TicketSlaPolicy;

final class TicketSlaPolicyRepository {
  private const OPTION = 'sbay_ticket_sla_policy';
  private const DEFAULTS = ['urgent' => 60, 'high' => 240, 'medium' => 480, 'normal' => 1440];

  public function get(): TicketSlaPolicy {
    $stored = get_option(self::OPTION, []);
    $stored = is_array($stored) ? $stored : [];
    $saved = isset($stored['first_response_minutes']) && is_array($stored['first_response_minutes'])
      ? $stored['first_response_minutes'] : [];
    $targets = self::DEFAULTS;
    foreach ($targets as $priority => $default) {
      $targets[$priority] = isset($saved[$priority]) ? (int) $saved[$priority] : $default;
    }

    return new TicketSlaPolicy(
      enabled: array_key_exists('enabled', $stored) ? (bool) $stored['enabled'] : true,
      firstResponseMinutes: $targets,
    );
  }

  public function save(TicketSlaPolicy $policy): void {
    update_option(self::OPTION, $policy->toArray(), false);
  }
}
