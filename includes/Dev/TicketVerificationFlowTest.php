<?php

declare(strict_types=1);

namespace SupportBay\Dev;

use RuntimeException;
use SupportBay\Core\Testing\Assert;
use SupportBay\Core\Testing\FlowTest;
use SupportBay\Modules\Tickets\Services\TicketService;
use SupportBay\Modules\Verifications\Enums\VerificationStatus;
use SupportBay\Modules\Verifications\Services\VerificationService;

final class TicketVerificationFlowTest extends FlowTest {
  /**
   * Test title.
   */
  protected static function title(): string {
    return 'Ticket Verification Flow Test';
  }

  /**
   * Execute flow.
   */
  protected static function execute(...$services): void {
    /** @var TicketService $tickets */
    /** @var VerificationService $verifications */
    [$tickets, $verifications] = $services;

    $reference = 'TICKET-' . strtoupper(
      wp_generate_password(16, false, false)
    );

    $verificationId = $verifications->create([
      'provider'            => 'fake-purchase',
      'provider_reference'  => $reference,
      'customer_id'         => 1,
      'verification_status' => VerificationStatus::VERIFIED,
      'product_id'          => '12345678',
      'product_name'        => 'SupportBay Test Product',
      'support_expires_at'  => '2030-01-01 00:00:00',
    ]);

    $firstTicketId = $tickets->create([
      'customer_id'              => 1,
      'subject'                  => 'Verified Ticket One',
      'purchase_verification_id' => $verificationId,
    ]);

    $firstTicket = $tickets->find($firstTicketId);

    Assert::notNull(
      $firstTicket,
      'Verified ticket is created.'
    );

    Assert::equals(
      $verificationId,
      $firstTicket->purchaseVerificationId(),
      'Ticket stores its purchase verification relationship.'
    );

    Assert::true(
      $firstTicket->hasPurchaseVerification(),
      'Ticket detects its purchase verification.'
    );

    $secondTicketId = $tickets->create([
      'customer_id'              => 1,
      'subject'                  => 'Verified Ticket Two',
      'purchase_verification_id' => $verificationId,
    ]);

    $relatedTickets = $tickets->findByVerification(
      $verificationId
    );

    Assert::count(
      2,
      $relatedTickets,
      'One verification resolves all related tickets.'
    );

    $ownershipRejected = false;

    try {
      $tickets->create([
        'customer_id'              => 2,
        'subject'                  => 'Invalid Ownership Ticket',
        'purchase_verification_id' => $verificationId,
      ]);
    } catch (RuntimeException) {
      $ownershipRejected = true;
    }

    Assert::true(
      $ownershipRejected,
      'Another customer cannot use the verification.'
    );

    $expiredVerificationId = $verifications->create([
      'provider'            => 'fake-purchase',
      'provider_reference'  => $reference . '-EXPIRED',
      'customer_id'         => 1,
      'verification_status' => VerificationStatus::VERIFIED,
      'support_expires_at'  => '2020-01-01 00:00:00',
    ]);
    $expiredRejected = false;

    try {
      $tickets->create([
        'customer_id'              => 1,
        'subject'                  => 'Expired Support Ticket',
        'purchase_verification_id' => $expiredVerificationId,
      ]);
    } catch (RuntimeException) {
      $expiredRejected = true;
    }

    Assert::true(
      $expiredRejected,
      'Expired support cannot be used to create a new ticket.'
    );

    $verifications->revoke($verificationId);

    $revokedRejected = false;

    try {
      $tickets->create([
        'customer_id'              => 1,
        'subject'                  => 'Revoked Verification Ticket',
        'purchase_verification_id' => $verificationId,
      ]);
    } catch (RuntimeException) {
      $revokedRejected = true;
    }

    Assert::true(
      $revokedRejected,
      'A revoked verification cannot be linked to a new ticket.'
    );

    Assert::true(
      $tickets->delete($firstTicketId),
      'First related ticket deleted.'
    );

    Assert::true(
      $tickets->delete($secondTicketId),
      'Second related ticket deleted.'
    );

    Assert::true(
      $verifications->delete($verificationId),
      'Test verification deleted.'
    );

    Assert::true(
      $verifications->delete($expiredVerificationId),
      'Expired test verification deleted.'
    );
  }
}
