<?php

declare(strict_types=1);

namespace SupportBay\Dev;

use SupportBay\Common\Enums\AuthorType;
use SupportBay\Core\Testing\Assert;
use SupportBay\Core\Testing\FlowTest;
use SupportBay\Modules\Customers\Services\CustomerService;
use SupportBay\Modules\Messages\Services\MessageService;
use SupportBay\Modules\Portal\Services\PortalService;
use SupportBay\Modules\Settings\Services\GeneralSettingsService;
use SupportBay\Modules\Tickets\Services\TicketService;

final class GuestTicketFlowTest extends FlowTest {
  protected static function title(): string {
    return 'Guest Ticket Flow Test';
  }

  protected static function execute(...$services): void {
    /** @var PortalService $portal */
    /** @var CustomerService $customers */
    /** @var TicketService $tickets */
    /** @var MessageService $messages */
    /** @var GeneralSettingsService $settings */
    [$portal, $customers, $tickets, $messages, $settings] = $services;
    $previous = $settings->get();
    $suffix = strtolower(wp_generate_password(10, false, false));
    $email = 'guest-ticket-' . $suffix . '@example.test';
    $ticketIds = [];
    $customerId = 0;
    $suppressMail = static fn(): bool => true;
    add_filter('pre_wp_mail', $suppressMail);

    try {
      $settings->update([
        'disable_guest_ticket_creation' => false,
        'client_user_default_role' => 'subscriber',
      ]);
      $first = $portal->createGuestTicket([
        'first_name' => 'Guest',
        'last_name' => 'Customer',
        'email' => $email,
        'subject' => 'Presales product question',
        'content' => 'Can this product support my workflow?',
      ]);
      $ticketIds[] = $first['ticket']->id();
      $customerId = $first['ticket']->customerId();
      $openingMessages = $messages->findByTicket($first['ticket']->id());

      Assert::true(
        $first['account_created']
        && $first['ticket']->purchaseVerificationId() === null
        && $openingMessages[0]->authorType() === AuthorType::GUEST,
        'A first guest submission creates a Subscriber-backed presales ticket without purchase verification.',
      );

      $second = $portal->createGuestTicket([
        'first_name' => 'Updated',
        'last_name' => 'Name',
        'email' => $email,
        'subject' => 'Second presales question',
        'content' => 'This should reuse the same customer account.',
      ]);
      $ticketIds[] = $second['ticket']->id();
      $profile = $customers->profile($customerId);

      Assert::true(
        ! $second['account_created']
        && $second['ticket']->customerId() === $customerId
        && $profile->displayName() === 'Updated Name',
        'A repeated guest email reuses the customer and refreshes the submitted name.',
      );
    } finally {
      foreach ($ticketIds as $ticketId) {
        foreach ($messages->findByTicket($ticketId) as $message) {
          $messages->delete($message->id());
        }
        $tickets->delete($ticketId);
      }
      if ($customerId > 0) {
        $customers->deleteWithUser($customerId);
      }
      $settings->update([
        'disable_guest_ticket_creation' => $previous['disable_guest_ticket_creation'],
        'client_user_default_role' => $previous['client_user_default_role'],
      ]);
      remove_filter('pre_wp_mail', $suppressMail);
    }
  }
}
