<?php

declare(strict_types=1);

namespace SupportBay\Modules\Portal\Http\Controllers;

use InvalidArgumentException;
use RuntimeException;
use SupportBay\Core\Http\RestResponse;
use SupportBay\Common\Utilities\RichTextSanitizer;
use SupportBay\Modules\Attachments\Entities\Attachment;
use SupportBay\Modules\Customers\Entities\Customer;
use SupportBay\Modules\Customers\Data\CustomerProfileData;
use SupportBay\Modules\Categories\Entities\Category;
use SupportBay\Modules\CustomFields\Entities\CustomField;
use SupportBay\Modules\Messages\Entities\Message;
use SupportBay\Modules\Portal\Services\PortalService;
use SupportBay\Modules\Tickets\Entities\Ticket;
use SupportBay\Modules\Tickets\Data\TicketQuery;
use SupportBay\Modules\Tickets\Enums\TicketPriority;
use SupportBay\Modules\Tickets\Enums\TicketState;
use SupportBay\Modules\Tickets\Enums\TicketStatus;
use SupportBay\Modules\Verifications\Entities\Verification;
use SupportBay\Modules\Settings\Services\GeneralSettingsService;
use SupportBay\Modules\Settings\Services\RecaptchaService;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

final class PortalController {
  private const NAMESPACE = 'sbay/v1';

  public function __construct(
    private readonly PortalService $portal,
    private readonly GeneralSettingsService $settings,
    private readonly RecaptchaService $recaptcha,
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

    register_rest_route(self::NAMESPACE, '/portal/profile', [
      'methods'             => 'GET',
      'callback'            => [$this, 'profile'],
      'permission_callback' => [$this, 'permissions'],
    ]);

    register_rest_route(self::NAMESPACE, '/portal/providers', [
      'methods'             => 'GET',
      'callback'            => [$this, 'providerConnections'],
      'permission_callback' => [$this, 'permissions'],
    ]);

    register_rest_route(self::NAMESPACE, '/portal/profile', [
      'methods'             => 'POST',
      'callback'            => [$this, 'updateProfile'],
      'permission_callback' => [$this, 'permissions'],
    ]);

    register_rest_route(self::NAMESPACE, '/portal/tickets', [
      'methods'             => 'GET',
      'callback'            => [$this, 'tickets'],
      'permission_callback' => [$this, 'permissions'],
    ]);

    register_rest_route(self::NAMESPACE, '/portal/guest-tickets', [
      'methods' => 'POST',
      'callback' => [$this, 'createGuestTicket'],
      'permission_callback' => [$this, 'guestPermissions'],
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

    foreach (['close', 'reopen'] as $action) {
      register_rest_route(
        self::NAMESPACE,
        '/portal/tickets/(?P<id>\d+)/' . $action,
        [
          'methods'             => 'POST',
          'callback'            => [$this, $action . 'Ticket'],
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
    }

    register_rest_route(self::NAMESPACE, '/portal/categories', [
      'methods'             => 'GET',
      'callback'            => [$this, 'categories'],
      'permission_callback' => [$this, 'permissions'],
    ]);
    register_rest_route(self::NAMESPACE, '/portal/tags', [
      'methods'             => 'GET',
      'callback'            => [$this, 'tags'],
      'permission_callback' => [$this, 'permissions'],
    ]);
    register_rest_route(self::NAMESPACE, '/portal/custom-fields', [
      'methods'             => 'GET',
      'callback'            => [$this, 'customFields'],
      'permission_callback' => [$this, 'permissions'],
      'args'                => [
        'category_id' => [
          'sanitize_callback' => 'absint',
        ],
      ],
    ]);

    register_rest_route(self::NAMESPACE, '/portal/verifications', [
      'methods'             => 'GET',
      'callback'            => [$this, 'verifications'],
      'permission_callback' => [$this, 'permissions'],
    ]);

    register_rest_route(self::NAMESPACE, '/portal/purchase-providers', [
      'methods'             => 'GET',
      'callback'            => [$this, 'purchaseProviders'],
      'permission_callback' => [$this, 'permissions'],
    ]);

    register_rest_route(
      self::NAMESPACE,
      '/portal/attachments/(?P<id>\d+)/download',
      [
        'methods'             => 'GET',
        'callback'            => [$this, 'downloadAttachment'],
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
   * Allow nonce-protected guest ticket requests when enabled.
   */
  public function guestPermissions(WP_REST_Request $request): bool|WP_Error {
    if (! $this->settings->guestTicketCreationEnabled()) {
      return new WP_Error(
        'sbay_guest_tickets_disabled',
        'Guest ticket creation is currently disabled.',
        ['status' => 403],
      );
    }

    $nonce = sanitize_text_field((string) $request->get_header('X-WP-Nonce'));

    return wp_verify_nonce($nonce, 'wp_rest')
      ? true
      : new WP_Error(
        'sbay_invalid_nonce',
        'The guest ticket request has expired.',
        ['status' => 403],
      );
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
   * Return the authenticated customer's profile.
   */
  public function profile(
    WP_REST_Request $request,
  ): WP_REST_Response {
    return RestResponse::success(
      $this->profileData($this->portal->profile()),
      'Customer profile retrieved.',
    );
  }

  /**
   * Update customer-editable profile fields.
   */
  public function updateProfile(
    WP_REST_Request $request,
  ): WP_REST_Response {
    $data = [];

    foreach (['company', 'phone', 'country', 'timezone', 'language'] as $field) {
      if ($request->has_param($field)) {
        $data[$field] = sanitize_text_field(
          wp_unslash((string) $request->get_param($field))
        );
      }
    }

    try {
      $profile = $this->portal->updateProfile($data);
    } catch (InvalidArgumentException|RuntimeException $exception) {
      return RestResponse::error(
        $exception->getMessage(),
        'PROFILE_UPDATE_FAILED',
        [],
        422,
      );
    }

    return RestResponse::success(
      $this->profileData($profile),
      'Customer profile updated.',
    );
  }

  public function providerConnections(
    WP_REST_Request $request,
  ): WP_REST_Response {
    return RestResponse::success(
      $this->portal->providerConnections(),
      'Customer provider connections retrieved.',
    );
  }

  /**
   * Return the current customer's tickets.
   */
  public function tickets(
    WP_REST_Request $request,
  ): WP_REST_Response {
    $page = max(1, absint($request->get_param('page')) ?: 1);
    $perPage = min(100, max(1, absint($request->get_param('per_page')) ?: 20));
    $status = sanitize_key((string) $request->get_param('status'));
    $state = sanitize_key((string) $request->get_param('state'));
    $priority = sanitize_key((string) $request->get_param('priority'));
    $result = $this->portal->searchTickets(new TicketQuery(
      page: $page,
      perPage: $perPage,
      search: sanitize_text_field(wp_unslash((string) $request->get_param('search'))) ?: null,
      status: TicketStatus::tryFrom($status)?->value,
      state: TicketState::tryFrom($state)?->value,
      priority: TicketPriority::tryFrom($priority)?->value,
      orderBy: sanitize_key((string) $request->get_param('orderby')),
      direction: sanitize_key((string) $request->get_param('order')),
    ));
    $categoryNames = [];
    foreach ($this->portal->categories() as $category) {
      $categoryNames[$category->id()] = $category->name();
    }
    $replySummaries = $this->portal->latestReplySummaries(array_map(
      static fn(Ticket $ticket): int => $ticket->id(),
      $result['items'],
    ));
    $tickets = array_map(
      function (Ticket $ticket) use ($categoryNames, $replySummaries): array {
        $reply = $replySummaries[$ticket->id()] ?? null;
        return array_merge($this->ticketData($ticket), [
          'category_name' => $ticket->categoryId() !== null
            ? ($categoryNames[$ticket->categoryId()] ?? null)
            : null,
          'reply_count' => $reply['reply_count'] ?? 0,
          'latest_reply_excerpt' => $reply !== null
            ? wp_trim_words(wp_strip_all_tags($reply['content']), 22, '…')
            : '',
          'has_support_reply' => $reply !== null && $reply['author_type']->isStaff(),
        ]);
      },
      $result['items'],
    );

    return RestResponse::success(
      $tickets,
      'Tickets retrieved.',
      [
        'page' => $page,
        'per_page' => $perPage,
        'total' => $result['total'],
        'total_pages' => (int) ceil($result['total'] / $perPage),
        'show_categories' => $categoryNames !== [],
      ]
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
        'content' => RichTextSanitizer::sanitize(
          wp_unslash((string) $request->get_param('content'))
        ),
        'category_id' => absint($request->get_param('category_id')) ?: null,
        'provider' => sanitize_key(
          (string) $request->get_param('provider')
        ),
        'purchase_reference' => sanitize_text_field(
          wp_unslash((string) $request->get_param('purchase_reference'))
        ),
        'custom_fields' => (array) $request->get_param('custom_fields'),
        'tag_ids' => (array) $request->get_param('tag_ids'),
      ]);
    } catch (InvalidArgumentException|RuntimeException $exception) {
      return RestResponse::error(
        $exception->getMessage(),
        'TICKET_CREATION_FAILED',
        [],
        422,
      );
    }

    $openingMessage = $this->portal->ticketMessages($ticket->id())[0] ?? null;

    return RestResponse::success(
      array_merge($this->ticketData($ticket), [
        'opening_message_id' => $openingMessage?->id(),
      ]),
      'Ticket created.',
      [],
      201,
    );
  }

  /**
   * Create a public presales ticket and resolve its customer by email.
   */
  public function createGuestTicket(
    WP_REST_Request $request,
  ): WP_REST_Response {
    try {
      $this->recaptcha->verify((string)$request->get_param('recaptcha_token'),'guest_ticket',isset($_SERVER['REMOTE_ADDR'])?sanitize_text_field((string)$_SERVER['REMOTE_ADDR']):null);
      $result = $this->portal->createGuestTicket([
        'first_name' => sanitize_text_field(
          wp_unslash((string) $request->get_param('first_name'))
        ),
        'last_name' => sanitize_text_field(
          wp_unslash((string) $request->get_param('last_name'))
        ),
        'email' => sanitize_email(
          wp_unslash((string) $request->get_param('email'))
        ),
        'subject' => sanitize_text_field(
          wp_unslash((string) $request->get_param('subject'))
        ),
        'content' => RichTextSanitizer::sanitize(
          wp_unslash((string) $request->get_param('content'))
        ),
        'file' => $request->get_file_params()['file'] ?? null,
      ]);
    } catch (InvalidArgumentException|RuntimeException $exception) {
      return RestResponse::error(
        $exception->getMessage(),
        'GUEST_TICKET_CREATION_FAILED',
        [],
        422,
      );
    }

    return RestResponse::success([
      'ticket' => $this->ticketData($result['ticket']),
      'account_created' => $result['account_created'],
    ], 'Guest ticket created.', [], 201);
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
    $category = null;
    foreach ($this->portal->categories() as $item) {
      if ($item->id() === $ticket->categoryId()) { $category = $item->name(); break; }
    }

    return RestResponse::success([
      'ticket'       => $this->ticketData($ticket),
      'messages'     => $messages,
      'verification' => $verification
        ? $this->verificationData($verification)
        : null,
      'information' => [
        'category' => $category,
        'status' => $ticket->status()->value,
      ],
      'tags' => array_map(
        static fn($tag): array => $tag->toArray(),
        $this->portal->ticketTags($ticket->id()),
      ),
      'custom_fields' => array_map(
        static fn(array $item): array => [
          'id' => $item['field']->id(),
          'name' => $item['field']->name(),
          'type' => $item['field']->type()->value,
          'value' => $item['value']->value(),
        ],
        $this->portal->ticketCustomFieldValues($ticket->id()),
      ),
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
        RichTextSanitizer::sanitize(
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
   * Close a customer-owned ticket.
   */
  public function closeTicket(
    WP_REST_Request $request,
  ): WP_REST_Response {
    return $this->transitionTicket($request, 'close');
  }

  /**
   * Reopen a customer-owned ticket.
   */
  public function reopenTicket(
    WP_REST_Request $request,
  ): WP_REST_Response {
    return $this->transitionTicket($request, 'reopen');
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
   * Authorize an attachment for the REST streaming hook.
   */
  public function downloadAttachment(
    WP_REST_Request $request,
  ): WP_REST_Response {
    try {
      $attachment = $this->portal->downloadableAttachment(
        (int) $request->get_param('id')
      );
    } catch (RuntimeException) {
      return RestResponse::error(
        'Attachment was not found.',
        'ATTACHMENT_NOT_FOUND',
        [],
        404,
      );
    }

    return RestResponse::success([
      'attachment_id' => $attachment->id(),
    ], 'Attachment authorized.');
  }

  /**
   * Stream an authorized attachment instead of serializing a REST response.
   */
  public function serveDownload(
    bool $served,
    mixed $result,
    WP_REST_Request $request,
    WP_REST_Server $server,
  ): bool {
    if (
      $served ||
      ! preg_match(
        '#^/sbay/v1/portal/attachments/(?P<id>\d+)/download$#',
        $request->get_route(),
        $matches,
      ) ||
      ! $result instanceof WP_REST_Response ||
      $result->get_status() !== 200
    ) {
      return $served;
    }

    try {
      $attachment = $this->portal->downloadableAttachment(
        (int) $matches['id']
      );
    } catch (RuntimeException) {
      return $served;
    }

    $handle = fopen($attachment->path(), 'rb');

    if ($handle === false) {
      return $served;
    }

    $filename = sanitize_file_name($attachment->originalName());

    if ($filename === '') {
      $filename = 'attachment.' . $attachment->extension();
    }

    while (ob_get_level() > 0) {
      ob_end_clean();
    }

    header('Content-Type: ' . $attachment->mimeType());
    header('Content-Length: ' . (string) filesize($attachment->path()));
    header('Content-Disposition: attachment; filename="' . $filename
      . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
    header('Cache-Control: private, no-store, max-age=0');
    header('X-Content-Type-Options: nosniff');

    fpassthru($handle);
    fclose($handle);

    $this->portal->recordAttachmentDownload($attachment->id());

    return true;
  }

  public function purchaseProviders(
    WP_REST_Request $request,
  ): WP_REST_Response {
    $providers = $this->portal->purchaseProviders();

    return RestResponse::success(
      $providers,
      'Purchase verification providers retrieved.',
      ['total' => count($providers)],
    );
  }

  public function categories(WP_REST_Request $request): WP_REST_Response {
    $categories = array_map(
      static fn(Category $category): array => [
        'id'            => $category->id(),
        'name'          => $category->name(),
        'description'   => $category->description(),
      ],
      $this->portal->categories(),
    );

    return RestResponse::success(
      $categories,
      'Categories retrieved.',
      ['total' => count($categories)],
    );
  }

  public function tags(WP_REST_Request $request): WP_REST_Response {
    $tags = array_map(
      static fn($tag): array => $tag->toArray(),
      $this->portal->tags(),
    );
    return RestResponse::success($tags, 'Tags retrieved.', ['total' => count($tags)]);
  }

  public function customFields(WP_REST_Request $request): WP_REST_Response {
    $fields = array_map(
      static fn(CustomField $field): array => [
        'id'            => $field->id(),
        'name'          => $field->name(),
        'slug'          => $field->slug(),
        'type'          => $field->type()->value,
        'options'       => $field->options(),
        'is_required'   => $field->isRequired(),
        'placeholder'   => $field->placeholder(),
        'sort_order'    => $field->sortOrder(),
      ],
      $this->portal->customFields(
        absint($request->get_param('category_id')) ?: null
      ),
    );

    return RestResponse::success(
      $fields,
      'Custom fields retrieved.',
      ['total' => count($fields)],
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
   * Customer profile fields safe for the authenticated account.
   *
   * @return array<string, mixed>
   */
  private function profileData(CustomerProfileData $profile): array {
    $customer = $profile->customer();

    return [
      'id'           => $customer->id(),
      'display_name' => $profile->displayName(),
      'email'        => $profile->email(),
      'avatar_url'   => $customer->avatarUrl(),
      'company'      => $customer->company(),
      'phone'        => $customer->phone(),
      'country'      => $customer->country(),
      'timezone'     => $customer->timezone(),
      'language'     => $customer->language(),
      'state'        => $customer->state()->value,
      'source'       => $customer->source()->value,
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
      'category_id'              => $ticket->categoryId(),
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
      'content'     => RichTextSanitizer::sanitize($message->content()),
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
   * Apply a customer ticket lifecycle transition.
   */
  private function transitionTicket(
    WP_REST_Request $request,
    string $action,
  ): WP_REST_Response {
    try {
      $ticket = $action === 'close'
        ? $this->portal->closeTicket((int) $request->get_param('id'))
        : $this->portal->reopenTicket((int) $request->get_param('id'));
    } catch (RuntimeException $exception) {
      return RestResponse::error(
        $exception->getMessage(),
        'TICKET_TRANSITION_FAILED',
        [],
        409,
      );
    }

    return RestResponse::success(
      $this->ticketData($ticket),
      $action === 'close' ? 'Ticket closed.' : 'Ticket reopened.',
    );
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
      'reference'          => \SupportBay\Modules\Verifications\Data\VerificationDirectoryItem::mask(
        $verification->providerReference()
      ),
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
