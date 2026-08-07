<?php

declare(strict_types=1);

namespace SupportBay\Dev;

use SupportBay\Common\Enums\AuthorType;
use SupportBay\Core\Testing\Assert;
use SupportBay\Core\Testing\FlowTest;
use SupportBay\Modules\Customers\Enums\CustomerSource;
use SupportBay\Modules\Customers\Enums\CustomerState;
use SupportBay\Modules\Customers\Services\CustomerService;
use SupportBay\Modules\Departments\Services\DepartmentService;
use SupportBay\Modules\Messages\Enums\MessageType;
use SupportBay\Modules\Messages\Services\MessageService;
use SupportBay\Modules\Tickets\Services\TicketService;

final class NotificationFlowTest extends FlowTest {
  protected static function title(): string {
    return 'Notification Flow Test';
  }

  protected static function execute(...$services): void {
    /** @var TicketService $tickets */
    /** @var MessageService $messages */
    /** @var CustomerService $customers */
    /** @var DepartmentService $departments */
    [$tickets, $messages, $customers, $departments] = $services;

    echo "🚀 Starting SupportBay Notification Flow Test...\n\n";

    $deliveries = [];
    $capture = static function (
      null|bool $return,
      array $attributes,
    ) use (&$deliveries): bool {
      $deliveries[] = $attributes;

      return true;
    };

    add_filter('pre_wp_mail', $capture, 10, 2);

    $suffix = strtolower(wp_generate_password(8, false, false));
    $userId = wp_insert_user([
      'user_login'   => 'sbay-notify-' . $suffix,
      'user_pass'    => wp_generate_password(32, true, true),
      'user_email'   => 'sbay-notify-' . $suffix . '@example.com',
      'display_name' => 'Notification Customer',
      'role'         => 'subscriber',
    ]);

    Assert::true(is_int($userId), 'Notification test user created.');

    $customerId = $customers->create([
      'user_id' => $userId,
      'state'   => CustomerState::REGISTERED->value,
      'source'  => CustomerSource::REGISTRATION->value,
    ]);
    $departmentId = $departments->create([
      'name' => 'Notification Test ' . $suffix,
      'slug' => 'notification-test-' . $suffix,
    ]);
    $ticketId = $tickets->create([
      'customer_id'   => $customerId,
      'department_id' => $departmentId,
      'subject'       => 'Notification delivery test',
    ]);

    Assert::count(
      2,
      $deliveries,
      'Ticket creation notifies the administrator and customer.'
    );

    $initial = $messages->create([
      'ticket_id'  => $ticketId,
      'author_id'  => $customerId,
      'author_type' => AuthorType::CUSTOMER->value,
      'content'    => 'Initial ticket message.',
    ]);

    Assert::count(
      2,
      $deliveries,
      'Initial ticket content does not create a duplicate reply email.'
    );

    $customerReply = $messages->create([
      'ticket_id'   => $ticketId,
      'author_id'   => $customerId,
      'author_type' => AuthorType::CUSTOMER->value,
      'content'     => 'Customer follow-up.',
    ]);

    Assert::count(
      3,
      $deliveries,
      'Customer reply notifies the administrator.'
    );

    $agentReply = $messages->create([
      'ticket_id'   => $ticketId,
      'author_id'   => 1,
      'author_type' => AuthorType::AGENT->value,
      'content'     => 'Agent response.',
    ]);

    Assert::equals(
      'sbay-notify-' . $suffix . '@example.com',
      $deliveries[3]['to'],
      'Agent reply notifies the ticket customer.'
    );

    $internal = $messages->create([
      'ticket_id'   => $ticketId,
      'author_id'   => 1,
      'author_type' => AuthorType::AGENT->value,
      'type'        => MessageType::INTERNAL_NOTE->value,
      'content'     => 'Private note.',
    ]);

    Assert::count(
      4,
      $deliveries,
      'Internal notes never send customer notifications.'
    );

    remove_filter('pre_wp_mail', $capture, 10);
    $messages->delete($internal->id());
    $messages->delete($agentReply->id());
    $messages->delete($customerReply->id());
    $messages->delete($initial->id());
    $tickets->delete($ticketId);
    $departments->delete($departmentId);
    $customers->deleteWithUser($customerId);
  }
}
