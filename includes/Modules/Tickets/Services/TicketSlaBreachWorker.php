<?php

declare(strict_types=1);

namespace SupportBay\Modules\Tickets\Services;

final class TicketSlaBreachWorker {
  public const HOOK='sbay_ticket_sla_breach_detection';
  public const SCHEDULE='sbay_sla_every_five_minutes';

  public function __construct(private readonly TicketSlaBreachService $breaches) {}
  public function register(): void { add_filter('cron_schedules',[$this,'schedules']); add_action('init',[$this,'ensureScheduled'],20); add_action(self::HOOK,[$this,'run']); }
  public function schedules(array $schedules): array { $schedules[self::SCHEDULE]=['interval'=>300,'display'=>did_action('init')>0?__('Every five minutes','supportbay'):'Every five minutes']; return $schedules; }
  public function ensureScheduled(): void { if(wp_next_scheduled(self::HOOK)===false){wp_schedule_event((int)current_time('timestamp',true)+300,self::SCHEDULE,self::HOOK);} }
  public function run(): array { return $this->breaches->detect(20); }
}
