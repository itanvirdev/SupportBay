<?php

declare(strict_types=1);

namespace SupportBay\Dev;

use SupportBay\Core\Authorization\CapabilityManager;
use SupportBay\Core\Testing\Assert;
use SupportBay\Core\Testing\FlowTest;
use SupportBay\Modules\Tickets\Services\TicketAccessPolicy;
use SupportBay\Modules\Tickets\Services\TicketService;

final class SecurityAuthorizationFlowTest extends FlowTest {
  protected static function title(): string { return 'Security Authorization Flow Test'; }

  protected static function execute(...$services): void {
    /** @var TicketService $tickets */
    /** @var TicketAccessPolicy $policy */
    [$tickets,$policy]=$services;
    CapabilityManager::register();
    $suffix=strtolower(wp_generate_password(8,false,false));
    $agentId=wp_create_user('sbay-sec-agent-'.$suffix,wp_generate_password(24),'agent-'.$suffix.'@example.test');
    $managerId=wp_create_user('sbay-sec-manager-'.$suffix,wp_generate_password(24),'manager-'.$suffix.'@example.test');
    (new \WP_User($agentId))->set_role('sbay_agent');
    (new \WP_User($managerId))->set_role('sbay_manager');
    $ownedId=$tickets->create(['customer_id'=>1,'subject'=>'Owned security ticket']);
    $otherId=$tickets->create(['customer_id'=>1,'subject'=>'Other security ticket']);
    $unassignedId=$tickets->create(['customer_id'=>1,'subject'=>'Unassigned security ticket']);
    try {
      $tickets->changeAssignment($ownedId,$agentId,$managerId);
      $tickets->changeAssignment($otherId,$managerId,$managerId);
      $tickets->changeAssignment($unassignedId,null,$managerId);
      Assert::true($policy->canView($tickets->find($ownedId),$agentId),'Agents can access their assigned tickets.');
      Assert::true($policy->canView($tickets->find($unassignedId),$agentId),'Agents can access permitted unassigned tickets.');
      Assert::true(!$policy->canView($tickets->find($otherId),$agentId),'Agents cannot access another staff member’s ticket.');
      Assert::true($policy->canView($tickets->find($otherId),$managerId),'Managers retain full queue access.');
      Assert::true(user_can($managerId,CapabilityManager::SHOW_TICKET_USER_EMAIL)&&!user_can($agentId,CapabilityManager::SHOW_TICKET_USER_EMAIL),'Ticket user email visibility is independently capability-gated.');
    } finally {
      foreach([$ownedId,$otherId,$unassignedId] as $ticketId){$tickets->delete($ticketId);}
      wp_delete_user($agentId);wp_delete_user($managerId);
    }
  }
}
