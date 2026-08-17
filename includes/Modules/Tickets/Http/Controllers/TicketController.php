<?php

declare(strict_types=1);

namespace SupportBay\Modules\Tickets\Http\Controllers;

use RuntimeException;
use SupportBay\Common\Enums\AuthorType;
use SupportBay\Common\Utilities\RichTextSanitizer;
use SupportBay\Core\Authorization\CapabilityManager;
use SupportBay\Core\Http\RestResponse;
use SupportBay\Modules\Messages\Enums\MessageType;
use SupportBay\Modules\Messages\Services\MessageService;
use SupportBay\Modules\Tickets\Services\TicketService;
use SupportBay\Modules\Tickets\Data\TicketQuery;
use SupportBay\Modules\Tickets\Enums\TicketPriority;
use SupportBay\Modules\Tickets\Enums\TicketState;
use SupportBay\Modules\Tickets\Enums\TicketSlaState;
use SupportBay\Modules\Tickets\Enums\TicketStatus;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class TicketController {
  private const NAMESPACE = 'sbay/v1';

  public function __construct(
    private readonly TicketService $tickets,
    private readonly MessageService $messages,
  ) {
  }

  public function registerRoutes(): void {
    register_rest_route(self::NAMESPACE, '/tickets', [
      'methods'             => 'GET',
      'callback'            => [$this, 'index'],
      'permission_callback' => [$this, 'permissions'],
      'args'                => $this->queryArgs(),
    ]);

    register_rest_route(self::NAMESPACE, '/tickets/(?P<id>\d+)', [
      'methods'             => 'GET',
      'callback'            => [$this, 'show'],
      'permission_callback' => [$this, 'permissions'],
      'args'                => $this->idArgs(),
    ]);

    register_rest_route(self::NAMESPACE, '/tickets/(?P<id>\d+)/messages', [
      'methods'             => 'GET',
      'callback'            => [$this, 'messages'],
      'permission_callback' => [$this, 'permissions'],
      'args'                => $this->idArgs(),
    ]);

    register_rest_route(self::NAMESPACE, '/tickets/(?P<id>\d+)/messages', [
      'methods'             => 'POST',
      'callback'            => [$this, 'reply'],
      'permission_callback' => [$this, 'canReply'],
      'args'                => $this->idArgs(),
    ]);

    foreach (['resolve', 'close', 'reopen'] as $action) {
      register_rest_route(
        self::NAMESPACE,
        '/tickets/(?P<id>\d+)/' . $action,
        [
          'methods'             => 'POST',
          'callback'            => [$this, $action],
          'permission_callback' => [$this, 'canChangeStatus'],
          'args'                => $this->idArgs(),
        ],
      );
    }
  }

  public function permissions(): bool|WP_Error {
    if (! is_user_logged_in()) {
      return new WP_Error(
        'sbay_authentication_required',
        'Authentication is required.',
        ['status' => 401],
      );
    }

    if (! current_user_can(CapabilityManager::VIEW_TICKETS)) {
      return new WP_Error(
        'sbay_permission_denied',
        'You are not allowed to manage support tickets.',
        ['status' => 403],
      );
    }

    return true;
  }

  public function canReply(WP_REST_Request $request): bool|WP_Error {
    $type = sanitize_key((string) $request->get_param('type'));
    $capability = $type === MessageType::INTERNAL_NOTE->value
      ? CapabilityManager::CREATE_INTERNAL_NOTE
      : CapabilityManager::REPLY_TICKET;

    return $this->requires($capability);
  }

  public function canChangeStatus(): bool|WP_Error {
    return $this->requires(CapabilityManager::CHANGE_TICKET_STATUS);
  }

  private function requires(string $capability): bool|WP_Error {
    if (! is_user_logged_in()) {
      return new WP_Error('sbay_authentication_required', 'Authentication is required.', ['status' => 401]);
    }

    return current_user_can($capability)
      ? true
      : new WP_Error('sbay_permission_denied', 'You are not allowed to perform this ticket action.', ['status' => 403]);
  }

  public function index(WP_REST_Request $request): WP_REST_Response {
    $page = max(1, (int) $request->get_param('page'));
    $perPage = min(100, max(1, (int) $request->get_param('per_page')));
    $status = sanitize_key((string) $request->get_param('status'));
    $state = sanitize_key((string) $request->get_param('state'));
    $priority = sanitize_key((string) $request->get_param('priority'));
    $assignment = sanitize_key((string) $request->get_param('assignment'));
    $agentId = absint($request->get_param('agent_id')) ?: null;
    $category = sanitize_text_field(
      (string) $request->get_param('category_id')
    );
    $result = $this->tickets->searchQueue(new TicketQuery(
      page: $page,
      perPage: $perPage,
      search: sanitize_text_field(wp_unslash((string) $request->get_param('search'))) ?: null,
      status: TicketStatus::tryFrom($status)?->value,
      state: TicketState::tryFrom($state)?->value,
      priority: TicketPriority::tryFrom($priority)?->value,
      assignedAgentId: $agentId ?? ($assignment === 'mine' ? get_current_user_id() : null),
      unassigned: $assignment === 'unassigned',
      departmentId: absint($request->get_param('department_id')) ?: null,
      categoryId: $category !== 'uncategorized'
        ? (absint($category) ?: null)
        : null,
      uncategorized: $category === 'uncategorized',
      tagId: absint($request->get_param('tag_id')) ?: null,
      needsReply: rest_sanitize_boolean($request->get_param('need_reply')),
      slaState: TicketSlaState::tryFrom(sanitize_key((string) $request->get_param('sla_state')))?->value,
      orderBy: sanitize_key((string) $request->get_param('orderby')),
      direction: sanitize_key((string) $request->get_param('order')),
    ));
    $total = $result['total'];

    return RestResponse::success(
      array_map(static fn($item): array => $item->toArray(), $result['items']),
      'Tickets retrieved.',
      [
        'page'        => $page,
        'per_page'    => $perPage,
        'total'       => $total,
        'total_pages' => (int) ceil($total / $perPage),
      ],
    );
  }

  public function show(WP_REST_Request $request): WP_REST_Response {
    $ticket = $this->tickets->find((int) $request->get_param('id'));

    if (! $ticket) {
      return RestResponse::error('Ticket was not found.', 'TICKET_NOT_FOUND', [], 404);
    }

    return RestResponse::success($ticket->toArray(), 'Ticket retrieved.');
  }

  public function messages(WP_REST_Request $request): WP_REST_Response {
    $ticketId = (int) $request->get_param('id');

    if (! $this->tickets->find($ticketId)) {
      return RestResponse::error('Ticket was not found.', 'TICKET_NOT_FOUND', [], 404);
    }

    $messages = array_map(
      static function ($message): array {
        $data = $message->toArray();
        $data['content'] = RichTextSanitizer::sanitize((string) $data['content']);
        return $data;
      },
      $this->messages->findByTicket($ticketId),
    );

    return RestResponse::success(
      $messages,
      'Messages retrieved.',
      ['total' => count($messages)],
    );
  }

  public function reply(WP_REST_Request $request): WP_REST_Response {
    $ticketId = (int) $request->get_param('id');
    $ticket = $this->tickets->find($ticketId);

    if (! $ticket) {
      return RestResponse::error('Ticket was not found.', 'TICKET_NOT_FOUND', [], 404);
    }

    if (! $ticket->status()->canReceiveReplies()) {
      return RestResponse::error('Finalized tickets cannot receive replies.', 'TICKET_FINALIZED', [], 409);
    }

    $type = MessageType::tryFrom(
      sanitize_key((string) $request->get_param('type')) ?: MessageType::REPLY->value
    );

    if (! $type || $type === MessageType::SYSTEM) {
      return RestResponse::error('Invalid message type.', 'INVALID_MESSAGE_TYPE', [], 422);
    }

    try {
      $message = $this->messages->create([
        'ticket_id'   => $ticketId,
        'author_id'   => get_current_user_id(),
        'author_type' => AuthorType::AGENT->value,
        'type'        => $type->value,
        'content'     => RichTextSanitizer::sanitize(
          wp_unslash((string) $request->get_param('content'))
        ),
      ]);
    } catch (RuntimeException|\InvalidArgumentException $exception) {
      return RestResponse::error($exception->getMessage(), 'MESSAGE_CREATION_FAILED', [], 422);
    }

    return RestResponse::success($message->toArray(), 'Reply created.', [], 201);
  }

  public function close(WP_REST_Request $request): WP_REST_Response {
    return $this->transition($request, 'close');
  }

  public function resolve(WP_REST_Request $request): WP_REST_Response {
    try {
      $ticket = $this->tickets->resolve(
        (int) $request->get_param('id'),
        get_current_user_id(),
      );
    } catch (RuntimeException $exception) {
      return RestResponse::error($exception->getMessage(), 'TICKET_TRANSITION_FAILED', [], 409);
    }

    return RestResponse::success($ticket->toArray(), 'Ticket resolved.');
  }

  public function reopen(WP_REST_Request $request): WP_REST_Response {
    return $this->transition($request, 'reopen');
  }

  private function transition(
    WP_REST_Request $request,
    string $action,
  ): WP_REST_Response {
    try {
      $ticket = $this->tickets->{$action}((int) $request->get_param('id'));
    } catch (RuntimeException $exception) {
      return RestResponse::error($exception->getMessage(), 'TICKET_TRANSITION_FAILED', [], 409);
    }

    return RestResponse::success($ticket->toArray(), 'Ticket updated.');
  }

  /** @return array<string, array<string, mixed>> */
  private function idArgs(): array {
    return [
      'id' => [
        'sanitize_callback' => 'absint',
        'validate_callback' => static fn(mixed $value): bool =>
          is_numeric($value) && (int) $value > 0,
      ],
    ];
  }

  /** @return array<string, array<string, mixed>> */
  private function queryArgs(): array {
    return [
      'page' => [
        'default'           => 1,
        'sanitize_callback' => 'absint',
      ],
      'per_page' => [
        'default'           => 20,
        'sanitize_callback' => 'absint',
      ],
      'search' => ['sanitize_callback' => 'sanitize_text_field'],
      'status' => ['sanitize_callback' => 'sanitize_key'],
      'state' => ['sanitize_callback' => 'sanitize_key'],
      'priority' => ['sanitize_callback' => 'sanitize_key'],
      'assignment' => ['sanitize_callback' => 'sanitize_key'],
      'agent_id' => ['sanitize_callback' => 'absint'],
      'department_id' => ['sanitize_callback' => 'absint'],
      'category_id' => ['sanitize_callback' => 'sanitize_text_field'],
      'tag_id' => ['sanitize_callback' => 'absint'],
      'need_reply' => ['default' => false, 'sanitize_callback' => 'rest_sanitize_boolean'],
      'orderby' => ['default' => 'updated_at', 'sanitize_callback' => 'sanitize_key'],
      'order' => ['default' => 'desc', 'sanitize_callback' => 'sanitize_key'],
    ];
  }
}
