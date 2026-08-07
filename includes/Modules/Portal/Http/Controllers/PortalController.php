<?php

declare(strict_types=1);

namespace SupportBay\Modules\Portal\Http\Controllers;

use InvalidArgumentException;
use RuntimeException;
use SupportBay\Core\Http\RestResponse;
use SupportBay\Modules\Attachments\Entities\Attachment;
use SupportBay\Modules\Customers\Entities\Customer;
use SupportBay\Modules\Departments\Entities\Department;
use SupportBay\Modules\Messages\Entities\Message;
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

    register_rest_route(self::NAMESPACE, '/portal/tickets', [
      'methods'             => 'POST',
      'callback'            => [$this, 'createTicket'],
      'permission_callback' => [$this, 'permissions'],
    ]);

    register_rest_route(
      self::NAMESPACE,
      '/portal/tickets/(?P<id>\d+)',
      [
        'methods'             => 'GET',
        'callback'            => [$this, 'ticket'],
        'permission_callback' => [$this, 'permissions'],
        'args'                => [
          'id' => [
            'sanitize_callback' => 'absint',
            'validate_callback' => static fn(mixed $value): bool =>
              is_numeric($value) && (int) $value > 0,
          ],
        ],
      ]
    );

    register_rest_route(
      self::NAMESPACE,
      '/portal/tickets/(?P<ticket_id>\d+)/messages/(?P<message_id>\d+)/attachments',
      [
        'methods'             => 'POST',
        'callback'            => [$this, 'uploadAttachment'],
        'permission_callback' => [$this, 'permissions'],
        'args'                => [
          'ticket_id' => [
            'sanitize_callback' => 'absint',
            'validate_callback' => static fn(mixed $value): bool =>
              is_numeric($value) && (int) $value > 0,
          ],
          'message_id' => [
            'sanitize_callback' => 'absint',
            'validate_callback' => static fn(mixed $value): bool =>
              is_numeric($value) && (int) $value > 0,
          ],
        ],
      ]
    );

    register_rest_route(
      self::NAMESPACE,
      '/portal/tickets/(?P<id>\d+)/replies',
      [
        'methods'             => 'POST',
        'callback'            => [$this, 'reply'],
        'permission_callback' => [$this, 'permissions'],
        'args'                => [
          'id' => [
            'sanitize_callback' => 'absint',
            'validate_callback' => static fn(mixed $value): bool =>
              is_numeric($value) && (int) $value > 0,
          ],
        ],
      ]
    );

    register_rest_route(self::NAMESPACE, '/portal/departments', [
      'methods'             => 'GET',
      'callback'            => [$this, 'departments'],
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
   * Create a customer ticket and opening message.
   */
  public function createTicket(
    WP_REST_Request $request,
  ): WP_REST_Response {
    try {
      $ticket = $this->portal->createTicket([
        'subject' => sanitize_text_field(
          wp_unslash((string) $request->get_param('subject'))
        ),
        'content' => sanitize_textarea_field(
          wp_unslash((string) $request->get_param('content'))
        ),
        'department_id' => absint($request->get_param('department_id')),
        'purchase_verification_id' => absint(
          $request->get_param('purchase_verification_id')
        ) ?: null,
      ]);
    } catch (InvalidArgumentException|RuntimeException $exception) {
      return RestResponse::error(
        $exception->getMessage(),
        'TICKET_CREATION_FAILED',
        [],
        422,
      );
    }

    return RestResponse::success(
      $this->ticketData($ticket),
      'Ticket created.',
      [],
      201,
    );
  }

  /**
   * Return one customer-owned ticket and its visible conversation.
   */
  public function ticket(
    WP_REST_Request $request,
  ): WP_REST_Response {
    try {
      $ticket = $this->portal->ticket(
        (int) $request->get_param('id')
      );
      $messages = array_map(
        fn(Message $message): array => $this->messageData($message),
        $this->portal->ticketMessages($ticket->id()),
      );
    } catch (RuntimeException) {
      return RestResponse::error(
        'Ticket was not found.',
        'TICKET_NOT_FOUND',
        [],
        404,
      );
    }

    $verification = $ticket->purchaseVerificationId() !== null
      ? $this->portal->verification($ticket->purchaseVerificationId())
      : null;

    return RestResponse::success([
      'ticket'       => $this->ticketData($ticket),
      'messages'     => $messages,
      'verification' => $verification
        ? $this->verificationData($verification)
        : null,
    ], 'Ticket retrieved.');
  }

  /**
   * Add a customer reply to a ticket.
   */
  public function reply(
    WP_REST_Request $request,
  ): WP_REST_Response {
    try {
      $message = $this->portal->reply(
        (int) $request->get_param('id'),
        sanitize_textarea_field(
          wp_unslash((string) $request->get_param('content'))
        ),
      );
    } catch (InvalidArgumentException|RuntimeException $exception) {
      return RestResponse::error(
        $exception->getMessage(),
        'TICKET_REPLY_FAILED',
        [],
        422,
      );
    }

    return RestResponse::success(
      $this->messageData($message),
      'Reply added.',
      [],
      201,
    );
  }

  /**
   * Upload one attachment to a customer-visible message.
   */
  public function uploadAttachment(
    WP_REST_Request $request,
  ): WP_REST_Response {
    $files = $request->get_file_params();
    $file = $files['file'] ?? null;

    if (! is_array($file)) {
      return RestResponse::error(
        'An attachment file is required.',
        'ATTACHMENT_REQUIRED',
        [],
        422,
      );
    }

    try {
      $attachment = $this->portal->uploadAttachment(
        (int) $request->get_param('ticket_id'),
        (int) $request->get_param('message_id'),
        $file,
      );
    } catch (InvalidArgumentException|RuntimeException $exception) {
      return RestResponse::error(
        $exception->getMessage(),
        'ATTACHMENT_UPLOAD_FAILED',
        [],
        422,
      );
    }

    return RestResponse::success(
      $this->attachmentData($attachment),
      'Attachment uploaded.',
      [],
      201,
    );
  }

  /**
   * Return active departments available to customers.
   */
  public function departments(
    WP_REST_Request $request,
  ): WP_REST_Response {
    $departments = array_map(
      fn(Department $department): array => [
        'id'          => $department->id(),
        'name'        => $department->name(),
        'description' => $department->description(),
      ],
      $this->portal->departments(),
    );

    return RestResponse::success(
      $departments,
      'Departments retrieved.',
      ['total' => count($departments)],
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
      'track_id'                 => $ticket->trackId(),
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
   * Customer-safe message fields.
   *
   * @return array<string, mixed>
   */
  private function messageData(Message $message): array {
    return [
      'id'          => $message->id(),
      'author_type' => $message->authorType()->value,
      'type'        => $message->type()->value,
      'content'     => wp_strip_all_tags($message->content()),
      'edited_at'   => $message->editedAt(),
      'created_at'  => $message->createdAt(),
      'attachments' => array_map(
        fn(Attachment $attachment): array => $this
          ->attachmentData($attachment),
        $this->portal->messageAttachments($message),
      ),
    ];
  }

  /**
   * Customer-safe attachment fields. Physical paths are never exposed.
   *
   * @return array<string, mixed>
   */
  private function attachmentData(Attachment $attachment): array {
    return [
      'id'             => $attachment->id(),
      'message_id'     => $attachment->messageId(),
      'original_name'  => $attachment->originalName(),
      'file_size'      => $attachment->fileSize(),
      'extension'      => $attachment->extension(),
      'mime_type'      => $attachment->mimeType(),
      'category'       => $attachment->category()->value,
      'is_previewable' => $attachment->canPreview(),
      'created_at'     => $attachment->createdAt(),
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
