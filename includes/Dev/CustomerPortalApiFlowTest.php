<?php

declare(strict_types=1);

namespace SupportBay\Dev;

use SupportBay\Core\Testing\Assert;
use SupportBay\Core\Testing\FlowTest;
use SupportBay\Common\Enums\AuthorType;
use SupportBay\Modules\Attachments\Services\AttachmentService;
use SupportBay\Modules\Customers\Enums\CustomerSource;
use SupportBay\Modules\Customers\Enums\CustomerState;
use SupportBay\Modules\Customers\Services\CustomerService;
use SupportBay\Modules\Categories\Services\CategoryService;
use SupportBay\Modules\CustomFields\Services\CustomFieldService;
use SupportBay\Modules\Departments\Services\DepartmentService;
use SupportBay\Modules\Messages\Enums\MessageType;
use SupportBay\Modules\Messages\Services\MessageService;
use SupportBay\Modules\Tickets\Services\TicketService;
use SupportBay\Modules\Verifications\Enums\VerificationStatus;
use SupportBay\Modules\Verifications\Services\VerificationService;
use WP_REST_Request;
use SupportBay\Core\Integrations\IntegrationManager;
use SupportBay\Modules\Providers\Enums\ProviderCategory;
use SupportBay\Modules\Providers\Enums\ProviderStatus;
use SupportBay\Modules\Providers\Services\ProviderService;
use SupportBay\Dev\FakeOAuthProvider;

final class CustomerPortalApiFlowTest extends FlowTest {
  protected static function title(): string {
    return 'Customer Portal API Flow Test';
  }

  protected static function execute(...$services): void {
    /** @var CustomerService $customers */
    /** @var TicketService $tickets */
    /** @var VerificationService $verifications */
    /** @var MessageService $messages */
    /** @var DepartmentService $departments */
    /** @var CategoryService $categories */
    /** @var AttachmentService $attachments */
    /** @var IntegrationManager $integrations */
    /** @var ProviderService $providers */
    /** @var CustomFieldService $customFields */
    [
      $customers,
      $tickets,
      $verifications,
      $messages,
      $departments,
      $categories,
      $attachments,
      $integrations,
      $providers,
      $customFields,
    ] = $services;

    foreach (['fake-purchase', 'fake-oauth'] as $testProviderSlug) {
      if ($integrations->has($testProviderSlug)) {
        $integrations->unregister($testProviderSlug);
      }
      $existingProvider = $providers->findBySlug($testProviderSlug);
      if ($existingProvider) {
        $providers->delete($existingProvider->id());
      }
    }

    $purchaseProvider = new FakePurchaseProvider();
    $integrations->register($purchaseProvider);
    $providerId = $providers->create([
      'slug' => $purchaseProvider->slug(),
      'name' => $purchaseProvider->name(),
      'category' => ProviderCategory::MARKETPLACE,
      'status' => ProviderStatus::ENABLED,
      'settings' => ['available' => true],
    ]);
    $oauthProvider = new FakeOAuthProvider();
    $integrations->register($oauthProvider);
    $oauthProviderId = $providers->create([
      'slug' => $oauthProvider->slug(),
      'name' => $oauthProvider->name(),
      'category' => ProviderCategory::MARKETPLACE,
      'status' => ProviderStatus::ENABLED,
      'settings' => [
        'available' => true,
        'oauth_login_enabled' => true,
      ],
    ]);

    $userId = wp_insert_user([
      'user_login' => 'sbay-portal-' . strtolower(
        wp_generate_password(12, false, false)
      ),
      'user_pass'  => wp_generate_password(32, true, true),
      'user_email' => 'portal-test-' . strtolower(
        wp_generate_password(8, false, false)
      ) . '@example.com',
      'display_name' => 'Portal Test Customer',
      'role'       => 'subscriber',
    ]);

    Assert::true(
      is_int($userId) && $userId > 0,
      'Temporary portal user created.'
    );

    $customerId = $customers->create([
      'user_id' => $userId,
      'state'   => CustomerState::REGISTERED->value,
      'source'  => CustomerSource::REGISTRATION->value,
    ]);

    $verificationId = $verifications->create([
      'provider'            => 'fake-purchase',
      'provider_reference'  => 'PORTAL-' . strtoupper(
        wp_generate_password(16, false, false)
      ),
      'customer_id'         => $customerId,
      'verification_status' => VerificationStatus::VERIFIED,
      'product_id'          => '12345678',
      'product_name'        => 'Portal Test Product',
      'support_expires_at'  => '2030-01-01 00:00:00',
      'provider_snapshot'   => [
        'secret' => 'must-not-be-exposed',
      ],
    ]);

    $expiredReference = 'EXPIRED-' . strtoupper(wp_generate_password(16, false, false));
    $expiredVerificationId = $verifications->create([
      'provider'            => 'fake-purchase',
      'provider_reference'  => $expiredReference,
      'customer_id'         => $customerId,
      'verification_status' => VerificationStatus::VERIFIED,
      'support_expires_at'  => '2020-01-01 00:00:00',
    ]);

    $departmentId = $departments->create([
      'name' => 'Portal Test Department ' . strtoupper(
        wp_generate_password(6, false, false)
      ),
    ]);
    $category = $categories->create([
      'name'          => 'Portal Category ' . strtoupper(
        wp_generate_password(6, false, false)
      ),
      'department_id' => $departmentId,
    ]);
    $customField = $customFields->create([
      'name'             => 'Site URL ' . $userId,
      'type'             => 'url',
      'is_required'      => true,
      'customer_visible' => true,
      'department_id'    => $departmentId,
    ]);
    $privateCustomField = $customFields->create([
      'name'             => 'Internal account tier ' . $userId,
      'type'             => 'text',
      'customer_visible' => false,
      'department_id'    => $departmentId,
    ]);

    $ticketId = $tickets->create([
      'customer_id'              => $customerId,
      'department_id'            => $departmentId,
      'subject'                  => 'Portal Test Ticket',
      'purchase_verification_id' => $verificationId,
    ]);
    $customFields->setValue(
      $ticketId,
      $customField->id(),
      'https://example.com/customer-site',
    );
    $customFields->setValue(
      $ticketId,
      $privateCustomField->id(),
      'Staff only',
    );

    $reply = $messages->create([
      'ticket_id'  => $ticketId,
      'author_id'  => $userId,
      'author_type' => 'customer',
      'type'       => MessageType::REPLY->value,
      'content'    => 'Customer-visible portal reply.',
    ]);

    $internalNote = $messages->create([
      'ticket_id'  => $ticketId,
      'author_id'  => 1,
      'author_type' => 'agent',
      'type'       => MessageType::INTERNAL_NOTE->value,
      'content'    => 'Private staff note.',
    ]);

    wp_set_current_user(0);

    $unauthenticated = rest_do_request(
      new WP_REST_Request('GET', '/sbay/v1/portal')
    );

    Assert::equals(
      401,
      $unauthenticated->get_status(),
      'Portal rejects unauthenticated requests.'
    );

    wp_set_current_user($userId);

    $overview = rest_do_request(
      new WP_REST_Request('GET', '/sbay/v1/portal')
    );
    $overviewData = $overview->get_data();

    Assert::equals(
      200,
      $overview->get_status(),
      'Authenticated customer can load portal bootstrap data.'
    );

    Assert::equals(
      $customerId,
      $overviewData['data']['customer']['id'] ?? null,
      'Portal resolves the current SupportBay customer.'
    );

    Assert::equals(
      1,
      $overviewData['data']['summary']['tickets'] ?? null,
      'Portal summary includes customer ticket count.'
    );

    $profileResponse = rest_do_request(
      new WP_REST_Request('GET', '/sbay/v1/portal/profile')
    );
    $profileData = $profileResponse->get_data();
    $originalEmail = $profileData['data']['email'] ?? null;

    Assert::equals(
      'Portal Test Customer',
      $profileData['data']['display_name'] ?? null,
      'Portal exposes the linked account identity.'
    );

    $invalidProfile = new WP_REST_Request(
      'POST',
      '/sbay/v1/portal/profile'
    );
    $invalidProfile->set_body_params([
      'timezone' => 'Not/A_Timezone',
    ]);

    Assert::equals(
      422,
      rest_do_request($invalidProfile)->get_status(),
      'Portal rejects invalid profile values.'
    );

    $profileUpdate = new WP_REST_Request(
      'POST',
      '/sbay/v1/portal/profile'
    );
    $profileUpdate->set_body_params([
      'company'  => 'SupportBay Test Company',
      'phone'    => '+880 1000 000000',
      'country'  => 'Bangladesh',
      'timezone' => 'Asia/Dhaka',
      'language' => 'en_US',
      'email'    => 'attempted-change@example.com',
    ]);
    $profileUpdateResponse = rest_do_request($profileUpdate);
    $updatedProfile = $profileUpdateResponse->get_data();

    Assert::equals(
      'SupportBay Test Company',
      $updatedProfile['data']['company'] ?? null,
      'Customer can update editable profile fields.'
    );

    Assert::equals(
      $originalEmail,
      $updatedProfile['data']['email'] ?? null,
      'Profile updates cannot change account identity fields.'
    );

    $oauthReference = 'PORTAL-OAUTH-CUSTOMER-' . $userId;
    $customers->connectProvider(
      $customerId,
      $oauthProvider->authenticateOAuth(
        'portal-connect',
        ['reference' => $oauthReference],
      ),
    );
    $providerConnectionsResponse = rest_do_request(
      new WP_REST_Request('GET', '/sbay/v1/portal/providers')
    );
    $providerConnections = $providerConnectionsResponse->get_data()['data'] ?? [];
    $oauthConnection = array_values(array_filter(
      $providerConnections,
      static fn(array $connection): bool =>
        ($connection['slug'] ?? '') === 'fake-oauth',
    ))[0] ?? [];

    Assert::true(
      ($oauthConnection['connected'] ?? false) === true
      && str_contains((string) ($oauthConnection['connect_url'] ?? ''), 'sbay_oauth=login')
      && ! str_contains((string) ($oauthConnection['reference'] ?? ''), $oauthReference),
      'Portal exposes a masked connected-provider summary and generic reconnect URL.'
    );

    Assert::false(
      array_key_exists('token', $oauthConnection),
      'Portal provider summaries never expose OAuth tokens.'
    );

    $ticketResponse = rest_do_request(
      new WP_REST_Request('GET', '/sbay/v1/portal/tickets')
    );
    $ticketData = $ticketResponse->get_data();

    Assert::equals(
      $ticketId,
      $ticketData['data'][0]['id'] ?? null,
      'Portal exposes only the current customer tickets.'
    );

    $detailResponse = rest_do_request(
      new WP_REST_Request(
        'GET',
        '/sbay/v1/portal/tickets/' . $ticketId
      )
    );
    $detailData = $detailResponse->get_data();

    Assert::equals(
      200,
      $detailResponse->get_status(),
      'Customer can load an owned ticket detail.'
    );

    Assert::equals(
      $reply->id(),
      $detailData['data']['messages'][0]['id'] ?? null,
      'Ticket detail exposes customer-visible messages.'
    );

    Assert::count(
      1,
      $detailData['data']['messages'] ?? [],
      'Ticket detail excludes internal notes.'
    );

    Assert::true(
      count($detailData['data']['custom_fields'] ?? []) === 1
      && ($detailData['data']['custom_fields'][0]['id'] ?? null) === $customField->id()
      && ($detailData['data']['custom_fields'][0]['value'] ?? null) === 'https://example.com/customer-site'
      && ! str_contains(wp_json_encode($detailData['data']['custom_fields']), 'Staff only'),
      'Ticket detail exposes stored customer-visible values without leaking staff-only fields.'
    );

    $verificationResponse = rest_do_request(
      new WP_REST_Request('GET', '/sbay/v1/portal/verifications')
    );
    $verificationData = $verificationResponse->get_data();

    Assert::equals(
      $verificationId,
      $verificationData['data'][0]['id'] ?? null,
      'Portal exposes the customer purchase verification.'
    );

    Assert::false(
      array_key_exists(
        'provider_snapshot',
        $verificationData['data'][0] ?? []
      ),
      'Portal does not expose provider snapshots.'
    );

    $departmentResponse = rest_do_request(
      new WP_REST_Request('GET', '/sbay/v1/portal/departments')
    );
    $departmentData = $departmentResponse->get_data();

    Assert::true(
      in_array(
        $departmentId,
        array_column($departmentData['data'] ?? [], 'id'),
        true,
      ),
      'Portal exposes active ticket departments.'
    );

    $categoryRequest = new WP_REST_Request(
      'GET',
      '/sbay/v1/portal/categories'
    );
    $categoryRequest->set_query_params([
      'department_id' => $departmentId,
    ]);
    $categoryResponse = rest_do_request($categoryRequest);

    Assert::true(
      in_array(
        $category->id(),
        array_column($categoryResponse->get_data()['data'] ?? [], 'id'),
        true,
      ),
      'Portal exposes categories applicable to the selected department.'
    );

    $customFieldRequest = new WP_REST_Request(
      'GET',
      '/sbay/v1/portal/custom-fields'
    );
    $customFieldRequest->set_query_params([
      'department_id' => $departmentId,
    ]);
    $customFieldResponse = rest_do_request($customFieldRequest);

    Assert::true(
      in_array(
        $customField->id(),
        array_column($customFieldResponse->get_data()['data'] ?? [], 'id'),
        true,
      ),
      'Portal exposes applicable customer-visible custom fields.'
    );

    $providerResponse = rest_do_request(
      new WP_REST_Request('GET', '/sbay/v1/portal/purchase-providers')
    );

    Assert::true(
      in_array('fake-purchase', array_column($providerResponse->get_data()['data'] ?? [], 'slug'), true),
      'Portal exposes provider-independent purchase verification options.'
    );

    $providers->disable($providerId);
    $disabledProviderResponse = rest_do_request(
      new WP_REST_Request('GET', '/sbay/v1/portal/purchase-providers')
    );

    Assert::false(
      in_array('fake-purchase', array_column($disabledProviderResponse->get_data()['data'] ?? [], 'slug'), true),
      'Portal hides disabled purchase providers.'
    );

    $providers->enable($providerId);

    $missingCategoryRequest = new WP_REST_Request(
      'POST',
      '/sbay/v1/portal/tickets'
    );
    $missingCategoryRequest->set_body_params([
      'subject'            => 'Missing category request',
      'content'            => 'This ticket must not be created.',
      'department_id'      => $departmentId,
      'provider'           => 'fake-purchase',
      'purchase_reference' => $verifications->find($verificationId)->providerReference(),
    ]);

    Assert::equals(
      422,
      rest_do_request($missingCategoryRequest)->get_status(),
      'Portal requires a category when the department has applicable categories.'
    );

    $expiredRequest = new WP_REST_Request('POST', '/sbay/v1/portal/tickets');
    $expiredRequest->set_body_params([
      'subject' => 'Expired support request',
      'content' => 'This ticket must not be created.',
      'department_id' => $departmentId,
      'category_id' => $category->id(),
      'provider' => 'fake-purchase',
      'purchase_reference' => $expiredReference,
      'custom_fields' => [
        (string) $customField->id() => 'https://example.com/expired-support',
      ],
    ]);
    $expiredResponse = rest_do_request($expiredRequest);

    Assert::true(
      $expiredResponse->get_status() === 422
      && str_contains($expiredResponse->get_data()['message'] ?? '', 'expired'),
      'Portal rejects Purchase Code/Key records whose support has expired.'
    );

    $missingCustomFieldRequest = new WP_REST_Request(
      'POST',
      '/sbay/v1/portal/tickets'
    );
    $missingCustomFieldRequest->set_body_params([
      'subject' => 'Missing custom field request',
      'content' => 'This ticket must not be created.',
      'department_id' => $departmentId,
      'category_id' => $category->id(),
      'provider' => 'fake-purchase',
      'purchase_reference' => $verifications->find($verificationId)->providerReference(),
    ]);

    Assert::equals(
      422,
      rest_do_request($missingCustomFieldRequest)->get_status(),
      'Portal enforces required customer custom fields.'
    );

    $createRequest = new WP_REST_Request(
      'POST',
      '/sbay/v1/portal/tickets'
    );
    $createRequest->set_body_params([
      'subject'                  => 'Created from customer portal',
      'content'                  => 'This is the opening portal message.',
      'department_id'            => $departmentId,
      'category_id'              => $category->id(),
      'provider'                  => 'fake-purchase',
      'purchase_reference'        => $verifications->find($verificationId)->providerReference(),
      'custom_fields'             => [
        $customField->id() => 'https://example.com/support',
      ],
    ]);
    $createResponse = rest_do_request($createRequest);
    $createData = $createResponse->get_data();
    $createdTicketId = (int) ($createData['data']['id'] ?? 0);

    Assert::equals(
      201,
      $createResponse->get_status(),
      'Customer can create a ticket through the portal.'
    );

    Assert::true(
      $createdTicketId > 0,
      'Portal returns the created ticket.'
    );

    Assert::equals(
      'https://example.com/support',
      $customFields->valuesForTicket($createdTicketId)[0]->value() ?? null,
      'Portal stores validated custom field values on the created ticket.'
    );

    Assert::equals(
      0,
      $purchaseProvider->verificationCalls(),
      'Existing purchase verification is reused without a provider API call.'
    );

    $newReference = 'NEW-' . strtoupper(wp_generate_password(16, false, false));
    $newEntitlement = $verifications->resolveTicketEntitlement(
      'fake-purchase',
      $newReference,
      $customerId,
    );
    $verifications->resolveTicketEntitlement(
      'fake-purchase',
      $newReference,
      $customerId,
    );

    Assert::equals(
      1,
      $purchaseProvider->verificationCalls(),
      'A new Purchase Code/Key calls its provider once and is cached for reuse.'
    );

    $openingMessage = $messages->findByTicket($createdTicketId)[0] ?? null;

    Assert::notNull(
      $openingMessage,
      'Portal-created ticket contains an opening message.'
    );

    $attachmentPath = wp_tempnam('portal-attachment.txt');
    file_put_contents($attachmentPath, 'Portal attachment test.');
    $attachmentId = $attachments->upload([
      'message_id'       => $openingMessage->id(),
      'ticket_id'        => $createdTicketId,
      'uploaded_by_id'   => $userId,
      'uploaded_by_type' => AuthorType::CUSTOMER->value,
      'original_name'    => 'portal-attachment.txt',
      'path'             => $attachmentPath,
      'file_size'        => filesize($attachmentPath),
      'extension'        => 'txt',
      'mime_type'        => 'text/plain',
    ]);

    $internalAttachmentPath = wp_tempnam('private-note.txt');
    file_put_contents($internalAttachmentPath, 'Private note attachment.');
    $internalAttachmentId = $attachments->upload([
      'message_id'       => $internalNote->id(),
      'ticket_id'        => $ticketId,
      'uploaded_by_id'   => 1,
      'uploaded_by_type' => AuthorType::AGENT->value,
      'original_name'    => 'private-note.txt',
      'path'             => $internalAttachmentPath,
      'file_size'        => filesize($internalAttachmentPath),
      'extension'        => 'txt',
      'mime_type'        => 'text/plain',
    ]);

    $attachmentDetail = rest_do_request(
      new WP_REST_Request(
        'GET',
        '/sbay/v1/portal/tickets/' . $createdTicketId
      )
    )->get_data();

    Assert::equals(
      $attachmentId,
      $attachmentDetail['data']['messages'][0]['attachments'][0]['id'] ?? null,
      'Portal exposes attachment metadata on visible messages.'
    );

    Assert::false(
      array_key_exists(
        'path',
        $attachmentDetail['data']['messages'][0]['attachments'][0] ?? []
      ),
      'Portal never exposes physical attachment paths.'
    );

    $downloadResponse = rest_do_request(
      new WP_REST_Request(
        'GET',
        '/sbay/v1/portal/attachments/' . $attachmentId . '/download'
      )
    );
    $downloadData = $downloadResponse->get_data();

    Assert::equals(
      $attachmentId,
      $downloadData['data']['attachment_id'] ?? null,
      'Customer can authorize a visible attachment download.'
    );

    $privateDownload = rest_do_request(
      new WP_REST_Request(
        'GET',
        '/sbay/v1/portal/attachments/' . $internalAttachmentId . '/download'
      )
    );

    Assert::equals(
      404,
      $privateDownload->get_status(),
      'Customer cannot download an internal-note attachment.'
    );

    wp_set_current_user(0);

    $unauthenticatedDownload = rest_do_request(
      new WP_REST_Request(
        'GET',
        '/sbay/v1/portal/attachments/' . $attachmentId . '/download'
      )
    );

    Assert::equals(
      401,
      $unauthenticatedDownload->get_status(),
      'Attachment downloads require authentication.'
    );

    wp_set_current_user($userId);

    $missingUpload = rest_do_request(
      new WP_REST_Request(
        'POST',
        '/sbay/v1/portal/tickets/' . $createdTicketId
          . '/messages/' . $openingMessage->id() . '/attachments'
      )
    );

    Assert::equals(
      422,
      $missingUpload->get_status(),
      'Portal rejects attachment requests without a file.'
    );

    $closeResponse = rest_do_request(
      new WP_REST_Request(
        'POST',
        '/sbay/v1/portal/tickets/' . $createdTicketId . '/close'
      )
    );
    $closeData = $closeResponse->get_data();

    Assert::equals(
      'closed',
      $closeData['data']['status'] ?? null,
      'Customer can close an owned ticket.'
    );

    $closedReplyRequest = new WP_REST_Request(
      'POST',
      '/sbay/v1/portal/tickets/' . $createdTicketId . '/replies'
    );
    $closedReplyRequest->set_body_params([
      'content' => 'This reply must be rejected while closed.',
    ]);
    $closedReplyResponse = rest_do_request($closedReplyRequest);

    Assert::equals(
      422,
      $closedReplyResponse->get_status(),
      'Closed tickets reject customer replies.'
    );

    $reopenResponse = rest_do_request(
      new WP_REST_Request(
        'POST',
        '/sbay/v1/portal/tickets/' . $createdTicketId . '/reopen'
      )
    );
    $reopenData = $reopenResponse->get_data();

    Assert::equals(
      'open',
      $reopenData['data']['status'] ?? null,
      'Customer can reopen a closed ticket.'
    );

    $replyRequest = new WP_REST_Request(
      'POST',
      '/sbay/v1/portal/tickets/' . $createdTicketId . '/replies'
    );
    $replyRequest->set_body_params([
      'content' => 'A follow-up customer reply.',
    ]);
    $replyResponse = rest_do_request($replyRequest);

    Assert::equals(
      201,
      $replyResponse->get_status(),
      'Customer can reply to an owned ticket.'
    );

    Assert::count(
      2,
      $messages->findByTicket($createdTicketId),
      'Created ticket contains its opening message and reply.'
    );

    wp_set_current_user(0);

    Assert::true(
      $messages->delete($reply->id()),
      'Test reply deleted.'
    );

    Assert::true(
      $messages->delete($internalNote->id()),
      'Test internal note deleted.'
    );

    foreach ($messages->findByTicket($createdTicketId) as $createdMessage) {
      $messages->delete($createdMessage->id());
    }

    Assert::true(
      $attachments->permanentlyDelete($attachmentId),
      'Test attachment and local file deleted.'
    );

    Assert::true(
      $attachments->permanentlyDelete($internalAttachmentId),
      'Test internal attachment and local file deleted.'
    );

    $customFields->removeValue($createdTicketId, $customField->id());
    $customFields->removeValue($ticketId, $customField->id());
    $customFields->removeValue($ticketId, $privateCustomField->id());

    Assert::true(
      $tickets->delete($createdTicketId),
      'Portal-created test ticket deleted.'
    );

    Assert::true(
      $tickets->delete($ticketId),
      'Test ticket deleted.'
    );

    Assert::true(
      $verifications->delete($verificationId),
      'Test verification deleted.'
    );

    Assert::true(
      $verifications->delete($newEntitlement->id()),
      'New entitlement cache record deleted.'
    );

    Assert::true(
      $verifications->delete($expiredVerificationId),
      'Expired portal entitlement deleted.'
    );

    Assert::true(
      $customers->deleteWithUser($customerId),
      'Test customer and WordPress user deleted.'
    );

    Assert::true(
      $categories->delete($category->id()),
      'Test category deleted.'
    );

    Assert::true(
      $customFields->delete($customField->id()),
      'Test custom field deleted.'
    );

    Assert::true(
      $customFields->delete($privateCustomField->id()),
      'Test private custom field deleted.'
    );

    Assert::true(
      $departments->delete($departmentId),
      'Test department deleted.'
    );

    Assert::true(
      $providers->delete($providerId),
      'Test purchase provider deleted.'
    );

    Assert::true(
      $providers->delete($oauthProviderId),
      'Test OAuth provider deleted.'
    );

    $integrations->unregister($purchaseProvider->slug());
    $integrations->unregister($oauthProvider->slug());
  }
}
