<?php

declare(strict_types=1);

namespace SupportBay\Dev;

use DateTimeImmutable;
use SupportBay\Common\Enums\AuthorType;
use SupportBay\Common\Enums\SourceType;
use SupportBay\Core\Testing\Assert;
use SupportBay\Core\Testing\FlowTest;
use SupportBay\Modules\Tickets\Data\TicketQuery;
use SupportBay\Modules\Tickets\Enums\TicketPriority;
use SupportBay\Modules\Tickets\Enums\TicketSlaState;
use SupportBay\Modules\Tickets\Enums\TicketState;
use SupportBay\Modules\Tickets\Enums\TicketStatus;
use SupportBay\Modules\Tickets\Repositories\TicketRepository;
use SupportBay\Modules\Tickets\Services\TicketService;
use SupportBay\Modules\Tickets\Services\TicketSlaPolicyService;

final class TicketSlaQueueFlowTest extends FlowTest {
  protected static function title(): string { return 'Ticket SLA Queue Flow Test'; }

  protected static function execute(...$services): void {
    /** @var TicketService $tickets */
    /** @var TicketRepository $repository */
    /** @var TicketSlaPolicyService $sla */
    [$tickets, $repository, $sla] = $services;
    $existing = get_option('sbay_ticket_sla_policy', null);
    $now = new DateTimeImmutable(current_time('mysql'));
    $agentId = 910000000 + wp_rand(1, 9999999);
    $ids = [];
    $fixtures = [
      ['met', -60, -10],
      ['breached', -180, -60],
      ['due-soon', -80, null],
      ['on-track', -20, null],
    ];

    try {
      $sla->update(['enabled' => true, 'first_response_minutes' => ['normal' => 100]]);
      foreach ($fixtures as [$subject, $createdOffset, $responseOffset]) {
        $ids[] = $repository->create([
          'track_id' => strtoupper(substr(wp_generate_password(9, false, false), 0, 9)),
          'customer_id' => 1, 'department_id' => 1, 'assigned_agent_id' => $agentId,
          'subject' => 'SLA ' . $subject, 'created_by_type' => AuthorType::CUSTOMER->value,
          'status' => TicketStatus::OPEN->value, 'state' => TicketState::ACTIVE->value,
          'priority' => TicketPriority::NORMAL->value, 'source' => SourceType::WEB->value,
          'first_response_at' => $responseOffset !== null ? $now->modify("{$responseOffset} minutes")->format('Y-m-d H:i:s') : null,
          'created_at' => $now->modify("{$createdOffset} minutes")->format('Y-m-d H:i:s'),
          'updated_at' => $now->format('Y-m-d H:i:s'),
        ]);
      }

      $page = $tickets->searchQueue(new TicketQuery(
        page: 1, perPage: 20, assignedAgentId: $agentId, orderBy: 'sla_due', direction: 'asc',
      ));
      $states = array_map(static fn($item): string => $item->toArray()['sla_state'], $page['items']);
      Assert::true(
        $page['total'] === 4
        && $states === ['breached', 'due_soon', 'on_track', 'met'],
        'SLA due-first sorting prioritizes breached and approaching tickets.',
      );

      foreach ([TicketSlaState::MET, TicketSlaState::BREACHED, TicketSlaState::DUE_SOON, TicketSlaState::ON_TRACK] as $state) {
        $filtered = $tickets->searchQueue(new TicketQuery(
          page: 1, perPage: 20, assignedAgentId: $agentId, slaState: $state->value,
        ));
        Assert::equals(1, $filtered['total'], "{$state->value} SLA queue filter returns its matching ticket.");
      }

      $sla->update(['enabled' => false]);
      $disabled = $tickets->searchQueue(new TicketQuery(page: 1, perPage: 20, assignedAgentId: $agentId));
      Assert::true(
        array_reduce($disabled['items'], static fn(bool $valid, $item): bool => $valid && $item->toArray()['sla_state'] === 'disabled', true),
        'Disabling the policy removes active SLA classification from every queue row.',
      );
    } finally {
      foreach ($ids as $id) { $repository->delete($id); }
      if ($existing === null) { delete_option('sbay_ticket_sla_policy'); }
      else { update_option('sbay_ticket_sla_policy', $existing, false); }
    }
  }
}
