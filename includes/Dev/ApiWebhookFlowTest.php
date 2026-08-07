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
use SupportBay\Modules\Messages\Services\MessageService;
use SupportBay\Modules\Tickets\Http\Controllers\TicketController;
use SupportBay\Modules\Tickets\Services\TicketService;
use SupportBay\Modules\Webhooks\Data\WebhookData;
use WP_Error;
use WP_REST_Request;

final class ApiWebhookFlowTest extends FlowTest {
  protected static function title(): string {
    return 'API and Webhook Flow Test';
  }

  protected static function execute(...$services): void {
    /** @var TicketController $controller */
    /** @var TicketService $tickets */
    /** @var MessageService $messages */
    /** @var CustomerService $customers */
    /** @var DepartmentService $departments */
    [$controller, $tickets, $messages, $customers, $departments] = $services;

    echo "🚀 Starting SupportBay API and Webhook Flow Test...\n\n";

    $webhooks = [];
    $captureWebhook = static function (WebhookData $webhook) use (&$webhooks): void {
      $webhooks[] = $webhook;
    };

    add_action('supportbay_webhook_dispatch', $captureWebhook);
    add_filter('pre_wp_mail', static fn(): bool => true);

    if (did_action('rest_api_init') === 0) {
      do_action('rest_api_init', rest_get_server());
    }

    $routes = rest_get_server()->get_routes();

    Assert::true(
      isset($routes['/sbay/v1/tickets']),
      'Versioned administrator ticket route is registered.'
    );

    wp_set_current_user(0);

    Assert::true(
      $controller->permissions() instanceof WP_Error,
      'Anonymous API access is rejected.'
    );

    wp_set_current_user(1);

    Assert::true(
      $controller->permissions() === true,
      'Administrator API access is allowed.'
    );

    $suffix = strtolower(wp_generate_password(8, false, false));
    $userId = wp_insert_user([
      'user_login' => 'sbay-api-' . $suffix,
      'user_pass'  => wp_generate_password(32, true, true),
      'user_email' => 'sbay-api-' . $suffix . '@example.com',
      'role'       => 'subscriber',
    ]);

    Assert::true(is_int($userId), 'API test customer account created.');

    $customerId = $customers->create([
      'user_id' => $userId,
      'state'   => CustomerState::REGISTERED->value,
      'source'  => CustomerSource::REGISTRATION->value,
    ]);
    $departmentId = $departments->create([
      'name' => 'API Test ' . $suffix,
      'slug' => 'api-test-' . $suffix,
    ]);
    $ticketId = $tickets->create([
      'customer_id'   => $customerId,
      'department_id' => $departmentId,
      'subject'       => 'API and webhook test',
    ]);
    $message = $messages->create([
      'ticket_id'   => $ticketId,
      'author_id'   => $customerId,
      'author_type' => AuthorType::CUSTOMER->value,
      'content'     => 'Webhook message payload.',
    ]);

    $request = new WP_REST_Request('GET', '/sbay/v1/tickets');
    $request->set_param('page', 1);
    $request->set_param('per_page', 10);
    $response = rest_do_request($request);
    $body = $response->get_data();

    Assert::equals(200, $response->get_status(), 'Ticket API returns HTTP 200.');
    Assert::true($body['success'] === true, 'Ticket API uses the standard response envelope.');
    Assert::true(isset($body['meta']['total_pages']), 'Ticket API includes pagination metadata.');

    $tickets->close($ticketId);
    $tickets->reopen($ticketId);

    Assert::equals(
      [
        'ticket.created',
        'message.created',
        'ticket.closed',
        'ticket.reopened',
      ],
      array_map(
        static fn(WebhookData $webhook): string => $webhook->event(),
        $webhooks,
      ),
      'Domain events emit normalized webhook payloads.'
    );

    Assert::equals(
      $ticketId,
      $webhooks[0]->payload()['ticket']['id'],
      'Webhook payload contains the domain entity data.'
    );

    remove_action('supportbay_webhook_dispatch', $captureWebhook);
    $messages->delete($message->id());
    $tickets->delete($ticketId);
    $departments->delete($departmentId);
    $customers->deleteWithUser($customerId);
  }
}
