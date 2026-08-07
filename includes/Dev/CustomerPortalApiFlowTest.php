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
use SupportBay\Modules\Departments\Services\DepartmentService;
use SupportBay\Modules\Messages\Enums\MessageType;
use SupportBay\Modules\Messages\Services\MessageService;
use SupportBay\Modules\Tickets\Services\TicketService;
use SupportBay\Modules\Verifications\Enums\VerificationStatus;
use SupportBay\Modules\Verifications\Services\VerificationService;
use WP_REST_Request;

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
    /** @var AttachmentService $attachments */
    [
      $customers,
      $tickets,
      $verifications,
      $messages,
      $departments,
      $attachments,
    ] = $services;

    $userId = wp_insert_user([
      'user_login' => 'sbay-portal-' . strtolower(
        wp_generate_password(12, false, false)
      ),
      'user_pass'  => wp_generate_password(32, true, true),
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
      'provider_snapshot'   => [
        'secret' => 'must-not-be-exposed',
      ],
    ]);

    $departmentId = $departments->create([
      'name' => 'Portal Test Department',
    ]);

    $ticketId = $tickets->create([
      'customer_id'              => $customerId,
      'department_id'            => $departmentId,
      'subject'                  => 'Portal Test Ticket',
      'purchase_verification_id' => $verificationId,
    ]);

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

    Assert::equals(
      $departmentId,
      $departmentData['data'][0]['id'] ?? null,
      'Portal exposes active ticket departments.'
    );

    $createRequest = new WP_REST_Request(
      'POST',
      '/sbay/v1/portal/tickets'
    );
    $createRequest->set_body_params([
      'subject'                  => 'Created from customer portal',
      'content'                  => 'This is the opening portal message.',
      'department_id'            => $departmentId,
      'purchase_verification_id' => $verificationId,
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
      $customers->deleteWithUser($customerId),
      'Test customer and WordPress user deleted.'
    );

    Assert::true(
      $departments->delete($departmentId),
      'Test department deleted.'
    );
  }
}
