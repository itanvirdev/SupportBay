<?php

declare(strict_types=1);

namespace SupportBay\Dev;

use SupportBay\Core\Testing\Assert;
use SupportBay\Core\Testing\FlowTest;
use SupportBay\Modules\Customers\Enums\CustomerSource;
use SupportBay\Modules\Customers\Enums\CustomerState;
use SupportBay\Modules\Customers\Services\CustomerService;
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
    [$customers, $tickets, $verifications] = $services;

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

    $ticketId = $tickets->create([
      'customer_id'              => $customerId,
      'department_id'            => 1,
      'subject'                  => 'Portal Test Ticket',
      'purchase_verification_id' => $verificationId,
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

    wp_set_current_user(0);

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
  }
}
