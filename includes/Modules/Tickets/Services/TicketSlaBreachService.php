<?php

declare(strict_types=1);

namespace SupportBay\Modules\Tickets\Services;

use SupportBay\Core\Events\EventDispatcher;
use SupportBay\Modules\Tickets\Events\TicketSlaBreached;
use SupportBay\Modules\Tickets\Repositories\TicketRepository;
use SupportBay\Modules\Tickets\Repositories\TicketSlaBreachRepository;

final class TicketSlaBreachService {
  public function __construct(
    private readonly TicketSlaBreachRepository $breaches,
    private readonly TicketRepository $tickets,
    private readonly TicketSlaPolicyService $policies,
    private readonly EventDispatcher $events,
  ) {
  }

  /** @return array{detected:int,dispatched:int} */
  public function detect(int $limit = 20, ?string $now = null): array {
    $policy = $this->policies->get();
    if (! $policy->enabled()) { return ['detected'=>0,'dispatched'=>0]; }
    $now = $now ?? current_time('mysql');
    $candidates = $this->breaches->findUnrecordedFirstResponseBreaches($now, $policy->firstResponseMinutes(), $limit);
    $dispatched = 0;
    foreach ($candidates as $candidate) {
      $ticket = $this->tickets->find($candidate['ticket_id']);
      if (! $ticket) { continue; }
      $breach = $this->breaches->claim($ticket->id(), $candidate['target_minutes'], $now);
      if (! $breach) { continue; }
      $this->events->dispatch(new TicketSlaBreached($ticket, $breach));
      $dispatched++;
    }
    return ['detected'=>count($candidates),'dispatched'=>$dispatched];
  }
}
