<?php

declare(strict_types=1);

namespace SupportBay\Dev;

use SupportBay\Common\Enums\AuthorType;
use SupportBay\Core\Testing\Assert;
use SupportBay\Core\Testing\FlowTest;
use SupportBay\Core\Integrations\IntegrationManager;
use SupportBay\Modules\Customers\Enums\CustomerSource;
use SupportBay\Modules\Customers\Enums\CustomerState;
use SupportBay\Modules\Customers\Services\CustomerService;
use SupportBay\Modules\Departments\Services\DepartmentService;
use SupportBay\Modules\Messages\Services\MessageService;
use SupportBay\Modules\Tickets\Http\Controllers\TicketController;
use SupportBay\Modules\Tickets\Services\TicketService;
use SupportBay\Modules\Providers\Services\ProviderService;
use SupportBay\Modules\Verifications\Services\VerificationService;
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
    /** @var ProviderService $providers */
    /** @var VerificationService $verifications */
    /** @var IntegrationManager $integrations */
    [$controller, $tickets, $messages, $customers, $departments, $providers, $verifications, $integrations] = $services;

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

    foreach (['customers', 'departments', 'providers', 'verifications'] as $resource) {
      Assert::true(
        isset($routes['/sbay/v1/' . $resource]),
        sprintf('Administrator %s route is registered.', $resource)
      );
    }

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
    $providerId = $providers->create([
      'slug'     => 'api-test-' . $suffix,
      'name'     => 'API Test Provider',
      'settings' => ['client_secret' => 'must-not-leak'],
    ]);
    $integrations->register(new FakePurchaseProvider());
    $verificationId = $verifications->verifyPurchase(
      'fake-purchase',
      'purchase-' . $suffix,
      customerId: $customerId,
    )->id();

    $request = new WP_REST_Request('GET', '/sbay/v1/tickets');
    $request->set_param('page', 1);
    $request->set_param('per_page', 10);
    $response = rest_do_request($request);
    $body = $response->get_data();

    Assert::equals(200, $response->get_status(), 'Ticket API returns HTTP 200.');
    Assert::true($body['success'] === true, 'Ticket API uses the standard response envelope.');
    Assert::true(isset($body['meta']['total_pages']), 'Ticket API includes pagination metadata.');

    $filteredRequest = new WP_REST_Request('GET', '/sbay/v1/tickets');
    $filteredRequest->set_param('search', 'API and webhook test');
    $filteredRequest->set_param('state', 'active');
    $filteredRequest->set_param('priority', 'normal');
    $filteredResponse = rest_do_request($filteredRequest)->get_data();

    Assert::true(
      $filteredResponse['meta']['total'] >= 1
      && $filteredResponse['data'][0]['id'] === $ticketId,
      'Ticket workspace API applies search, state, and priority filters.'
    );

    $customerResponse = rest_do_request(
      new WP_REST_Request('GET', '/sbay/v1/customers/' . $customerId)
    );
    Assert::equals(200, $customerResponse->get_status(), 'Customer detail API is available.');

    $providerResponse = rest_do_request(
      new WP_REST_Request('GET', '/sbay/v1/providers/' . $providerId)
    );
    $providerData = $providerResponse->get_data()['data'];
    Assert::false(isset($providerData['settings']), 'Provider credentials are excluded from API output.');
    Assert::true($providerData['configured'] === true, 'Provider API reports configuration state safely.');

    $verificationRequest = new WP_REST_Request('GET', '/sbay/v1/verifications');
    $verificationRequest->set_param('provider', 'fake-purchase');
    $verificationResponse = rest_do_request($verificationRequest);
    Assert::equals(
      true,
      $verificationResponse->get_data()['meta']['total'] >= 1,
      'Verification API applies provider filters.'
    );

    $wordpressUser = get_userdata($userId);
    $wordpressUser->set_role('sbay_agent');
    wp_set_current_user($userId);

    Assert::equals(
      200,
      rest_do_request(new WP_REST_Request('GET', '/sbay/v1/tickets'))->get_status(),
      'Support agents can view tickets through capabilities.'
    );
    Assert::equals(
      403,
      rest_do_request(new WP_REST_Request('GET', '/sbay/v1/customers'))->get_status(),
      'Support agents cannot manage customers.'
    );

    wp_set_current_user(1);

    $customerState = new WP_REST_Request('POST', '/sbay/v1/customers/' . $customerId . '/state');
    $customerState->set_param('state', CustomerState::SUSPENDED->value);
    Assert::equals(200, rest_do_request($customerState)->get_status(), 'Administrator can suspend customers.');

    $departmentUpdate = new WP_REST_Request('PUT', '/sbay/v1/departments/' . $departmentId);
    $departmentUpdate->set_param('status', 'inactive');
    Assert::equals(200, rest_do_request($departmentUpdate)->get_status(), 'Administrator can update departments.');

    Assert::equals(
      200,
      rest_do_request(new WP_REST_Request('POST', '/sbay/v1/providers/' . $providerId . '/enable'))->get_status(),
      'Administrator can enable providers.'
    );
    Assert::equals(
      200,
      rest_do_request(new WP_REST_Request('POST', '/sbay/v1/verifications/' . $verificationId . '/refresh'))->get_status(),
      'Administrator can refresh verifications through registered integrations.'
    );
    Assert::equals(
      200,
      rest_do_request(new WP_REST_Request('POST', '/sbay/v1/verifications/' . $verificationId . '/revoke'))->get_status(),
      'Administrator can revoke verifications.'
    );

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
    $verifications->delete($verificationId);
    $providers->delete($providerId);
    $messages->delete($message->id());
    $tickets->delete($ticketId);
    $departments->delete($departmentId);
    $customers->deleteWithUser($customerId);
  }
}
