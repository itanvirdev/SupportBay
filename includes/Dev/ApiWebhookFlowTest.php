<?php

declare(strict_types=1);

namespace SupportBay\Dev;

use SupportBay\Common\Enums\AuthorType;
use SupportBay\Common\Utilities\RichTextSanitizer;
use SupportBay\Core\Testing\Assert;
use SupportBay\Core\Testing\FlowTest;
use SupportBay\Core\Integrations\IntegrationManager;
use SupportBay\Modules\Customers\Enums\CustomerSource;
use SupportBay\Modules\Customers\Enums\CustomerState;
use SupportBay\Modules\Customers\Services\CustomerService;
use SupportBay\Modules\Categories\Services\CategoryService;
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
    /** @var CategoryService $categories */
    /** @var ProviderService $providers */
    /** @var VerificationService $verifications */
    /** @var IntegrationManager $integrations */
    [$controller, $tickets, $messages, $customers, $departments, $categories, $providers, $verifications, $integrations] = $services;

    echo "🚀 Starting SupportBay API and Webhook Flow Test...\n\n";

    $richText = RichTextSanitizer::sanitize(
      '<p style="text-align:center;background:red"><strong>Safe</strong>'
      . '<a href="javascript:alert(1)">link</a></p><table><tr><td>Cell</td></tr></table>'
      . '<iframe src="https://example.com"></iframe><script>alert(1)</script>'
    );
    Assert::true(
      str_contains($richText, '<strong>Safe</strong>')
      && str_contains($richText, 'text-align: center')
      && ! str_contains($richText, 'background')
      && ! str_contains($richText, 'javascript:')
      && ! str_contains($richText, '<table')
      && ! str_contains($richText, '<iframe')
      && ! str_contains($richText, '<script'),
      'Rich text sanitizer preserves supported formatting and removes unsafe markup.'
    );

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
    Assert::true(
      isset($routes['/sbay/v1/admin/tickets/(?P<id>\d+)/context']),
      'Agent ticket context route is registered.'
    );
    Assert::true(
      isset($routes['/sbay/v1/admin/tickets/(?P<ticket_id>\d+)/messages/(?P<message_id>\d+)/attachments']),
      'Protected agent message attachment route is registered.'
    );
    Assert::true(
      isset($routes['/sbay/v1/admin/attachments/(?P<id>\d+)/download']),
      'Protected agent attachment download route is registered.'
    );
    Assert::true(
      isset($routes['/sbay/v1/admin/tickets/(?P<id>\d+)/actions']),
      'Capability-protected ticket operations route is registered.'
    );
    Assert::true(
      isset($routes['/sbay/v1/admin/tickets/options']),
      'Agent queue filter options route is registered.'
    );
    Assert::true(
      isset($routes['/sbay/v1/admin/tickets/bulk-actions']),
      'Capability-protected ticket bulk action route is registered.'
    );
    Assert::true(
      isset($routes['/sbay/v1/admin/tickets/(?P<id>\d+)/merge']),
      'Manager ticket merge route is registered.'
    );
    Assert::true(
      isset($routes['/sbay/v1/admin/tickets/(?P<id>\d+)/split']),
      'Manager ticket split route is registered.'
    );
    Assert::true(
      isset($routes['/sbay/v1/tickets/(?P<id>\d+)/resolve']),
      'Capability-protected ticket resolution route is registered.'
    );
    Assert::true(
      isset($routes['/sbay/v1/admin/customers/(?P<id>\d+)/profile']),
      'Capability-protected Customer 360 profile route is registered.'
    );
    Assert::true(
      isset($routes['/sbay/v1/admin/customers/directory']),
      'Paginated Customer Directory route is registered.'
    );

    foreach (['customers', 'departments', 'categories', 'providers', 'verifications'] as $resource) {
      Assert::true(
        isset($routes['/sbay/v1/' . $resource]),
        sprintf('Administrator %s route is registered.', $resource)
      );
    }
    Assert::true(
      isset($routes['/sbay/v1/providers/(?P<id>\d+)/configuration']),
      'Secret-safe provider configuration route is registered.'
    );
    Assert::true(
      isset($routes['/sbay/v1/providers/(?P<id>\d+)/test-connection']),
      'Capability-aware provider connection test route is registered.'
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
    $otherDepartmentId = $departments->create([
      'name' => 'API Other ' . $suffix,
      'slug' => 'api-other-' . $suffix,
    ]);
    $category = $categories->create([
      'name'          => 'API Category ' . $suffix,
      'department_id' => $departmentId,
    ]);
    $ticketId = $tickets->create([
      'customer_id'   => $customerId,
      'department_id' => $departmentId,
      'category_id'   => $category->id(),
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
    update_user_meta($userId, 'sbay_oauth_fake-purchase_identity', 'provider-customer-' . $suffix);

    $request = new WP_REST_Request('GET', '/sbay/v1/tickets');
    $request->set_param('page', 1);
    $request->set_param('per_page', 10);
    $response = rest_do_request($request);
    $body = $response->get_data();

    Assert::equals(200, $response->get_status(), 'Ticket API returns HTTP 200.');
    Assert::true($body['success'] === true, 'Ticket API uses the standard response envelope.');
    Assert::true(isset($body['meta']['total_pages']), 'Ticket API includes pagination metadata.');

    $contextResponse = rest_do_request(
      new WP_REST_Request('GET', '/sbay/v1/admin/tickets/' . $ticketId . '/context')
    );
    $context = $contextResponse->get_data()['data'];
    Assert::true(
      $contextResponse->get_status() === 200
      && $context['customer']['email'] !== ''
      && $context['information']['department'] !== null
      && $context['information']['category'] !== null
      && in_array($category->id(), array_column($context['options']['categories'], 'id'), true)
      && is_array($context['activities']),
      'Agent ticket context composes safe customer, department, and activity data.'
    );

    $inUseCategoryDelete = rest_do_request(
      new WP_REST_Request(
        'DELETE',
        '/sbay/v1/categories/' . $category->id()
      )
    );
    Assert::equals(
      409,
      $inUseCategoryDelete->get_status(),
      'Categories referenced by tickets must be deactivated instead of deleted.'
    );

    $categoryChange = new WP_REST_Request(
      'POST',
      '/sbay/v1/admin/tickets/' . $ticketId . '/actions'
    );
    $categoryChange->set_param('action', 'category');
    $categoryChange->set_param('value', '');
    $categoryChangeResponse = rest_do_request($categoryChange);
    $categoryContext = rest_do_request(
      new WP_REST_Request(
        'GET',
        '/sbay/v1/admin/tickets/' . $ticketId . '/context'
      )
    )->get_data()['data'];

    Assert::true(
      $categoryChangeResponse->get_status() === 200
      && $tickets->find($ticketId)?->categoryId() === null
      && array_filter(
        $categoryContext['activities'],
        static fn(array $activity): bool =>
          $activity['label'] === 'Category Changed',
      ) !== [],
      'Staff category changes persist and create timeline activity.'
    );

    $categoryChange->set_param('value', $category->id());
    Assert::equals(
      200,
      rest_do_request($categoryChange)->get_status(),
      'Staff can select a category applicable to the ticket department.'
    );

    $departmentChange = new WP_REST_Request(
      'POST',
      '/sbay/v1/admin/tickets/' . $ticketId . '/actions'
    );
    $departmentChange->set_param('action', 'department');
    $departmentChange->set_param('value', $otherDepartmentId);
    Assert::true(
      rest_do_request($departmentChange)->get_status() === 200
      && $tickets->find($ticketId)?->categoryId() === null,
      'Moving a ticket clears a category that is invalid for the new department.'
    );

    $categoryChange->set_param('value', $category->id());
    Assert::equals(
      422,
      rest_do_request($categoryChange)->get_status(),
      'Staff cannot select a category scoped to another department.'
    );

    $departmentChange->set_param('value', $departmentId);
    rest_do_request($departmentChange);
    $categoryChange->set_param('value', $category->id());
    rest_do_request($categoryChange);

    $categoryFilter = new WP_REST_Request('GET', '/sbay/v1/tickets');
    $categoryFilter->set_param('category_id', $category->id());
    $categoryFilterResponse = rest_do_request($categoryFilter)->get_data();
    Assert::true(
      $categoryFilterResponse['meta']['total'] >= 1
      && $categoryFilterResponse['data'][0]['category_id'] === $category->id()
      && $categoryFilterResponse['data'][0]['category_name'] === $category->name(),
      'Ticket queue filters by category and exposes its safe display name.'
    );

    $categoryChange->set_param('value', '');
    rest_do_request($categoryChange);
    $categoryFilter->set_param('category_id', 'uncategorized');
    $uncategorizedResponse = rest_do_request($categoryFilter)->get_data();
    Assert::true(
      $uncategorizedResponse['meta']['total'] >= 1
      && in_array(
        $ticketId,
        array_column($uncategorizedResponse['data'], 'id'),
        true,
      ),
      'Ticket queue explicitly filters uncategorized tickets.'
    );

    $categoryChange->set_param('value', $category->id());
    rest_do_request($categoryChange);

    $filteredRequest = new WP_REST_Request('GET', '/sbay/v1/tickets');
    $filteredRequest->set_param('search', 'API and webhook test');
    $filteredRequest->set_param('state', 'active');
    $filteredRequest->set_param('priority', 'normal');
    $filteredResponse = rest_do_request($filteredRequest)->get_data();

    Assert::true(
      $filteredResponse['meta']['total'] >= 1
      && $filteredResponse['data'][0]['id'] === $ticketId
      && $filteredResponse['data'][0]['reply_count'] >= 1
      && $filteredResponse['data'][0]['needs_reply'] === true
      && $filteredResponse['data'][0]['customer_name'] !== null
      && $filteredResponse['data'][0]['department_name'] !== null,
      'Ticket workspace API returns search-filtered queue intelligence.'
    );

    $needReplyRequest = new WP_REST_Request('GET', '/sbay/v1/tickets');
    $needReplyRequest->set_param('search', 'API and webhook test');
    $needReplyRequest->set_param('need_reply', true);
    $needReplyResponse = rest_do_request($needReplyRequest)->get_data();
    Assert::true(
      $needReplyResponse['meta']['total'] >= 1
      && $needReplyResponse['data'][0]['id'] === $ticketId,
      'Need Reply filter includes tickets whose latest public reply is from the customer.'
    );

    $customerResponse = rest_do_request(
      new WP_REST_Request('GET', '/sbay/v1/customers/' . $customerId)
    );
    Assert::equals(200, $customerResponse->get_status(), 'Customer detail API is available.');

    $profileResponse = rest_do_request(
      new WP_REST_Request('GET', '/sbay/v1/admin/customers/' . $customerId . '/profile')
    );
    $profileData = $profileResponse->get_data()['data'];
    Assert::true(
      $profileResponse->get_status() === 200
      && $profileData['customer']['email'] !== ''
      && $profileData['summary']['tickets'] >= 1
      && $profileData['summary']['purchases'] >= 1
      && $profileData['tickets'][0]['id'] === $ticketId
      && $profileData['providers'][0]['provider'] === 'fake-purchase'
      && ! str_contains($profileData['providers'][0]['reference'], $suffix)
      && ! isset($profileData['purchases'][0]['provider_snapshot']),
      'Customer 360 composes safe identity, provider, purchase, and ticket history data.'
    );

    $directoryRequest = new WP_REST_Request('GET', '/sbay/v1/admin/customers/directory');
    $directoryRequest->set_param('search', 'sbay-api-' . $suffix);
    $directoryRequest->set_param('state', CustomerState::REGISTERED->value);
    $directoryRequest->set_param('source', CustomerSource::REGISTRATION->value);
    $directoryResponse = rest_do_request($directoryRequest);
    $directoryData = $directoryResponse->get_data();
    Assert::true(
      $directoryResponse->get_status() === 200
      && $directoryData['meta']['total'] === 1
      && $directoryData['data'][0]['id'] === $customerId
      && $directoryData['data'][0]['ticket_count'] >= 1
      && $directoryData['data'][0]['purchase_count'] >= 1
      && ! isset($directoryData['data'][0]['user_id']),
      'Customer Directory applies server-side identity and lifecycle filters with support context counts.'
    );

    $providerResponse = rest_do_request(
      new WP_REST_Request('GET', '/sbay/v1/providers/' . $providerId)
    );
    $providerData = $providerResponse->get_data()['data'];
    Assert::false(isset($providerData['settings']), 'Provider credentials are excluded from API output.');
    Assert::true($providerData['configured'] === true, 'Provider API reports configuration state safely.');

    $verificationRequest = new WP_REST_Request('GET', '/sbay/v1/verifications');
    $verificationRequest->set_param('provider', 'fake-purchase');
    $verificationRequest->set_param('status', 'verified');
    $verificationRequest->set_param('search', 'purchase-' . $suffix);
    $verificationResponse = rest_do_request($verificationRequest);
    $verificationBody = $verificationResponse->get_data();
    Assert::equals(
      true,
      $verificationBody['meta']['total'] >= 1
      && $verificationBody['data'][0]['provider'] === 'fake-purchase'
      && $verificationBody['data'][0]['verification_status'] === 'verified'
      && ! str_contains($verificationBody['data'][0]['reference'], $suffix)
      && ! isset($verificationBody['data'][0]['provider_reference'])
      && ! isset($verificationBody['data'][0]['provider_snapshot']),
      'Verification Directory combines server filters and returns masked purchase data.'
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
    Assert::equals(
      403,
      rest_do_request(new WP_REST_Request('GET', '/sbay/v1/admin/customers/' . $customerId . '/profile'))->get_status(),
      'Support agents cannot open Customer 360 profiles without customer-management capability.'
    );
    Assert::equals(
      403,
      rest_do_request(new WP_REST_Request('GET', '/sbay/v1/admin/customers/directory'))->get_status(),
      'Support agents cannot search the Customer Directory without customer-management capability.'
    );
    $deniedBulk = new WP_REST_Request('POST', '/sbay/v1/admin/tickets/bulk-actions');
    $deniedBulk->set_param('ticket_ids', [$ticketId]);
    $deniedBulk->set_param('action', 'assignment');
    $deniedBulk->set_param('value', 'me');
    Assert::equals(403, rest_do_request($deniedBulk)->get_status(), 'Agents cannot use manager-only bulk assignment.');
    $deniedSplit = new WP_REST_Request('POST', '/sbay/v1/admin/tickets/' . $ticketId . '/split');
    $deniedSplit->set_param('message_ids', [$message->id()]);
    $deniedSplit->set_param('subject', 'Denied split');
    Assert::equals(403, rest_do_request($deniedSplit)->get_status(), 'Agents cannot use manager-only ticket splitting.');

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

    $otherTicketId = $tickets->create([
      'customer_id' => $customerId,
      'department_id' => $otherDepartmentId,
      'subject' => 'API bulk category scope test',
    ]);
    $categoryChange->set_param('value', '');
    rest_do_request($categoryChange);

    $bulkCategory = new WP_REST_Request(
      'POST',
      '/sbay/v1/admin/tickets/bulk-actions'
    );
    $bulkCategory->set_param('ticket_ids', [$ticketId, $otherTicketId]);
    $bulkCategory->set_param('action', 'category');
    $bulkCategory->set_param('value', $category->id());
    $bulkCategoryResponse = rest_do_request($bulkCategory);
    Assert::true(
      $bulkCategoryResponse->get_status() === 200
      && $bulkCategoryResponse->get_data()['meta']['updated'] === 1
      && $bulkCategoryResponse->get_data()['meta']['failed'] === 1
      && $tickets->find($ticketId)?->categoryId() === $category->id()
      && $tickets->find($otherTicketId)?->categoryId() === null,
      'Bulk classification updates compatible tickets and reports scoped failures.'
    );

    $bulkPriority = new WP_REST_Request('POST', '/sbay/v1/admin/tickets/bulk-actions');
    $bulkPriority->set_param('ticket_ids', [$ticketId]);
    $bulkPriority->set_param('action', 'priority');
    $bulkPriority->set_param('value', 'high');
    $bulkResponse = rest_do_request($bulkPriority);
    Assert::true(
      $bulkResponse->get_status() === 200
      && $bulkResponse->get_data()['meta']['updated'] === 1
      && $tickets->find($ticketId)?->priority()->value === 'high',
      'Administrator bulk action updates selected tickets through the ticket service.'
    );

    $splitMessage = $messages->create([
      'ticket_id' => $ticketId,
      'author_id' => 1,
      'author_type' => AuthorType::AGENT->value,
      'content' => 'This conversation belongs in a separate ticket.',
    ]);
    $splitRequest = new WP_REST_Request('POST', '/sbay/v1/admin/tickets/' . $ticketId . '/split');
    $splitRequest->set_param('message_ids', [$splitMessage->id()]);
    $splitRequest->set_param('subject', 'API split ticket');
    $splitResponse = rest_do_request($splitRequest);
    $splitTicketId = (int) $splitResponse->get_data()['data']['id'];
    $splitContext = rest_do_request(
      new WP_REST_Request('GET', '/sbay/v1/admin/tickets/' . $splitTicketId . '/context')
    )->get_data()['data'];
    Assert::true(
      $splitResponse->get_status() === 201
      && count($messages->findByTicket($ticketId)) === 1
      && count($messages->findByTicket($splitTicketId)) === 1
      && $messages->find($splitMessage->id())?->ticketId() === $splitTicketId
      && array_filter($splitContext['activities'], static fn(array $activity): bool => $activity['label'] === 'Ticket Split') !== [],
      'Ticket split creates a related ticket, moves selected entries, repairs both queues, and records audit activity.'
    );

    $targetTicketId = $tickets->create([
      'customer_id' => $customerId,
      'department_id' => $departmentId,
      'subject' => 'API merge target',
    ]);
    $targetMessage = $messages->create([
      'ticket_id' => $targetTicketId,
      'author_id' => $customerId,
      'author_type' => AuthorType::CUSTOMER->value,
      'content' => 'Merge target opening message.',
    ]);
    $mergeRequest = new WP_REST_Request('POST', '/sbay/v1/admin/tickets/' . $ticketId . '/merge');
    $mergeRequest->set_param('target_id', $targetTicketId);
    $mergeResponse = rest_do_request($mergeRequest);
    $mergedSource = $tickets->find($ticketId);
    $mergeContext = rest_do_request(
      new WP_REST_Request('GET', '/sbay/v1/admin/tickets/' . $targetTicketId . '/context')
    )->get_data()['data'];
    Assert::true(
      $mergeResponse->get_status() === 200
      && $mergedSource?->state()->value === 'trash'
      && $messages->findByTicket($ticketId) === []
      && count($messages->findByTicket($targetTicketId)) === 2
      && array_filter($mergeContext['activities'], static fn(array $activity): bool => $activity['label'] === 'Ticket Merged') !== [],
      'Ticket merge preserves the conversation, retires the source, and records an audit activity.'
    );

    $verifications->delete($verificationId);
    $providers->delete($providerId);
    $messages->delete($message->id());
    $messages->delete($splitMessage->id());
    $messages->delete($targetMessage->id());
    $tickets->delete($ticketId);
    $tickets->delete($splitTicketId);
    $tickets->delete($targetTicketId);
    $tickets->delete($otherTicketId);
    $categories->delete($category->id());
    $departments->delete($departmentId);
    $departments->delete($otherDepartmentId);
    $customers->deleteWithUser($customerId);
  }
}
