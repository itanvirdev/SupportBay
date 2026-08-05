<?php

declare(strict_types=1);

namespace SupportBay\Modules\Portal\Http\Controllers;

use RuntimeException;
use SupportBay\Core\Http\RestResponse;
use SupportBay\Modules\Customers\Entities\Customer;
use SupportBay\Modules\Portal\Services\PortalService;
use SupportBay\Modules\Tickets\Entities\Ticket;
use SupportBay\Modules\Verifications\Entities\Verification;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class PortalController {
  private const NAMESPACE = 'sbay/v1';

  public function __construct(
    private readonly PortalService $portal,
  ) {
  }

  /**
   * Register customer portal endpoints.
   */
  public function registerRoutes(): void {
    register_rest_route(self::NAMESPACE, '/portal', [
      'methods'             => 'GET',
      'callback'            => [$this, 'overview'],
      'permission_callback' => [$this, 'permissions'],
    ]);

    register_rest_route(self::NAMESPACE, '/portal/tickets', [
      'methods'             => 'GET',
      'callback'            => [$this, 'tickets'],
      'permission_callback' => [$this, 'permissions'],
    ]);

    register_rest_route(self::NAMESPACE, '/portal/verifications', [
      'methods'             => 'GET',
      'callback'            => [$this, 'verifications'],
      'permission_callback' => [$this, 'permissions'],
    ]);
  }

  /**
   * Require a logged-in SupportBay customer.
   */
  public function permissions(): bool|WP_Error {
    if (! is_user_logged_in()) {
      return new WP_Error(
        'sbay_authentication_required',
        'Authentication is required.',
        ['status' => 401]
      );
    }

    try {
      $this->portal->currentCustomer();
    } catch (RuntimeException $exception) {
      return new WP_Error(
        'sbay_customer_required',
        $exception->getMessage(),
        ['status' => 403]
      );
    }

    return true;
  }

  /**
   * Return portal bootstrap data.
   */
  public function overview(
    WP_REST_Request $request,
  ): WP_REST_Response {
    $customer = $this->portal->currentCustomer();
    $tickets = $this->portal->tickets();
    $verifications = $this->portal->verifications();

    return RestResponse::success([
      'customer' => $this->customerData($customer),
      'summary'  => [
        'tickets'      => count($tickets),
        'verifications' => count($verifications),
      ],
    ], 'Customer portal loaded.');
  }

  /**
   * Return the current customer's tickets.
   */
  public function tickets(
    WP_REST_Request $request,
  ): WP_REST_Response {
    $tickets = array_map(
      fn(Ticket $ticket): array => $this->ticketData($ticket),
      $this->portal->tickets(),
    );

    return RestResponse::success(
      $tickets,
      'Tickets retrieved.',
      ['total' => count($tickets)]
    );
  }

  /**
   * Return the current customer's purchase verifications.
   */
  public function verifications(
    WP_REST_Request $request,
  ): WP_REST_Response {
    $verifications = array_map(
      fn(Verification $verification): array => $this
        ->verificationData($verification),
      $this->portal->verifications(),
    );

    return RestResponse::success(
      $verifications,
      'Purchase verifications retrieved.',
      ['total' => count($verifications)]
    );
  }

  /**
   * Customer-safe API fields.
   *
   * @return array<string, mixed>
   */
  private function customerData(Customer $customer): array {
    return [
      'id'            => $customer->id(),
      'state'         => $customer->state()->value,
      'source'        => $customer->source()->value,
      'avatar_url'    => $customer->avatarUrl(),
      'company'       => $customer->company(),
      'country'       => $customer->country(),
      'timezone'      => $customer->timezone(),
      'language'      => $customer->language(),
      'last_login_at' => $customer->lastLoginAt(),
    ];
  }

  /**
   * Ticket-safe API fields.
   *
   * @return array<string, mixed>
   */
  private function ticketData(Ticket $ticket): array {
    return [
      'id'                       => $ticket->id(),
      'subject'                  => $ticket->subject(),
      'status'                   => $ticket->status()->value,
      'priority'                 => $ticket->priority()->value,
      'source'                   => $ticket->source()->value,
      'purchase_verification_id' => $ticket->purchaseVerificationId(),
      'created_at'               => $ticket->createdAt(),
      'updated_at'               => $ticket->updatedAt(),
    ];
  }

  /**
   * Verification-safe API fields.
   *
   * @return array<string, mixed>
   */
  private function verificationData(
    Verification $verification,
  ): array {
    return [
      'id'                 => $verification->id(),
      'provider'           => $verification->provider(),
      'product_id'         => $verification->productId(),
      'product_name'       => $verification->productName(),
      'license_type'       => $verification->licenseType(),
      'support_expires_at' => $verification->supportExpiresAt(),
      'purchased_at'       => $verification->purchasedAt(),
      'status'             => $verification->status()->value,
      'verified_at'        => $verification->verifiedAt(),
    ];
  }
}
