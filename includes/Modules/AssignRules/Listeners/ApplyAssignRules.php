<?php

declare(strict_types=1);

namespace SupportBay\Modules\AssignRules\Listeners;

use SupportBay\Core\Events\Contracts\Event;
use SupportBay\Core\Events\Contracts\Listener;
use SupportBay\Modules\AssignRules\Services\AssignRuleService;
use SupportBay\Modules\Tickets\Events\TicketCreated;

final class ApplyAssignRules implements Listener {
  public function __construct(private readonly AssignRuleService $rules) {}
  public function handle(Event $event): void {
    if ($event instanceof TicketCreated) { $this->rules->applyToTicket($event->ticket()); }
  }
}
