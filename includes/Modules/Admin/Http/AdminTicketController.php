<?php

declare(strict_types=1);

namespace SupportBay\Modules\Admin\Http;

use RuntimeException;
use SupportBay\Core\Authorization\CapabilityManager;
use SupportBay\Core\Http\RestResponse;
use SupportBay\Modules\Activities\Services\ActivityService;
use SupportBay\Common\Enums\AuthorType;
use SupportBay\Modules\Attachments\Services\AttachmentService;
use SupportBay\Modules\Messages\Services\MessageService;
use SupportBay\Modules\Customers\Services\CustomerService;
use SupportBay\Modules\Categories\Services\CategoryService;
use SupportBay\Modules\CustomFields\Services\CustomFieldService;
use SupportBay\Modules\Tags\Services\TagService;
use SupportBay\Modules\Departments\Services\DepartmentService;
use SupportBay\Modules\Tickets\Services\TicketService;
use SupportBay\Modules\Tickets\Services\TicketMergeService;
use SupportBay\Modules\Tickets\Services\TicketSplitService;
use SupportBay\Modules\Verifications\Services\VerificationService;
use SupportBay\Modules\Tickets\Enums\TicketPriority;
use SupportBay\Modules\Tickets\Enums\TicketState;
use SupportBay\Modules\Tickets\Enums\TicketBulkAction;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

final class AdminTicketController {
  public function __construct(
    private readonly TicketService $tickets,
    private readonly CustomerService $customers,
    private readonly DepartmentService $departments,
    private readonly CategoryService $categories,
    private readonly CustomFieldService $customFields,
    private readonly TagService $tags,
    private readonly VerificationService $verifications,
    private readonly ActivityService $activities,
    private readonly MessageService $messages,
    private readonly AttachmentService $attachments,
    private readonly TicketMergeService $ticketMerger,
    private readonly TicketSplitService $ticketSplitter,
  ) {
  }

  public function registerRoutes(): void {
    register_rest_route('sbay/v1', '/admin/tickets/options', [
      'methods' => 'GET', 'callback' => [$this, 'options'],
      'permission_callback' => [$this, 'permissions'],
    ]);
    register_rest_route('sbay/v1', '/admin/tickets/bulk-actions', [
      'methods' => 'POST',
      'callback' => [$this, 'bulkChangeTickets'],
      'permission_callback' => [$this, 'permissions'],
    ]);
    register_rest_route('sbay/v1', '/admin/tickets/(?P<id>\d+)/merge', [
      'methods' => 'POST',
      'callback' => [$this, 'mergeTicket'],
      'permission_callback' => static fn(): bool|WP_Error => current_user_can('sbay_merge_ticket')
        ? true
        : new WP_Error('sbay_permission_denied', 'You are not allowed to merge tickets.', ['status' => 403]),
      'args' => ['id' => ['sanitize_callback' => 'absint']],
    ]);
    register_rest_route('sbay/v1', '/admin/tickets/(?P<id>\d+)/split', [
      'methods' => 'POST',
      'callback' => [$this, 'splitTicket'],
      'permission_callback' => static fn(): bool|WP_Error => current_user_can('sbay_split_ticket')
        ? true
        : new WP_Error('sbay_permission_denied', 'You are not allowed to split tickets.', ['status' => 403]),
      'args' => ['id' => ['sanitize_callback' => 'absint']],
    ]);
    register_rest_route('sbay/v1', '/admin/tickets/(?P<id>\d+)/context', [
      'methods' => 'GET',
      'callback' => [$this, 'show'],
      'permission_callback' => [$this, 'permissions'],
      'args' => ['id' => ['sanitize_callback' => 'absint']],
    ]);
    register_rest_route('sbay/v1', '/admin/tickets/(?P<ticket_id>\d+)/messages/(?P<message_id>\d+)/attachments', [
      'methods' => 'POST',
      'callback' => [$this, 'uploadAttachment'],
      'permission_callback' => static fn(): bool|WP_Error => current_user_can(CapabilityManager::REPLY_TICKET)
        ? true : new WP_Error('sbay_permission_denied', 'You are not allowed to upload attachments.', ['status' => 403]),
      'args' => [
        'ticket_id' => ['sanitize_callback' => 'absint'],
        'message_id' => ['sanitize_callback' => 'absint'],
      ],
    ]);
    register_rest_route('sbay/v1', '/admin/attachments/(?P<id>\d+)/download', [
      'methods' => 'GET',
      'callback' => [$this, 'downloadAttachment'],
      'permission_callback' => static fn(): bool|WP_Error => current_user_can(CapabilityManager::VIEW_TICKETS)
        ? true : new WP_Error('sbay_permission_denied', 'You are not allowed to download attachments.', ['status' => 403]),
      'args' => ['id' => ['sanitize_callback' => 'absint']],
    ]);
    register_rest_route('sbay/v1', '/admin/tickets/(?P<id>\d+)/actions', [
      'methods' => 'POST', 'callback' => [$this, 'changeTicket'],
      'permission_callback' => [$this, 'permissions'],
      'args' => ['id' => ['sanitize_callback' => 'absint']],
    ]);
  }

  public function permissions(): bool|WP_Error {
    if (! is_user_logged_in()) {
      return new WP_Error('sbay_authentication_required', 'Authentication is required.', ['status' => 401]);
    }

    return current_user_can(CapabilityManager::VIEW_TICKETS)
      ? true
      : new WP_Error('sbay_permission_denied', 'You are not allowed to view tickets.', ['status' => 403]);
  }

  public function show(WP_REST_Request $request): WP_REST_Response {
    $ticket = $this->tickets->find((int) $request->get_param('id'));

    if (! $ticket) {
      return RestResponse::error('Ticket was not found.', 'TICKET_NOT_FOUND', [], 404);
    }

    $customer = null;
    if ($ticket->hasCustomer()) {
      try {
        $profile = $this->customers->profile($ticket->customerId());
        $customer = [
          'id' => $profile->customer()->id(),
          'name' => $profile->displayName(),
          'email' => $profile->email(),
          'avatar_url' => $profile->customer()->avatarUrl(),
          'state' => $profile->customer()->state()->value,
        ];
      } catch (RuntimeException) {
        $customer = null;
      }
    }

    $department = $this->departments->find($ticket->departmentId());
    $category = $ticket->categoryId() !== null
      ? $this->categories->find($ticket->categoryId())
      : null;
    $verification = $ticket->purchaseVerificationId() !== null
      ? $this->verifications->find($ticket->purchaseVerificationId())
      : null;
    $agent = $ticket->assignedAgentId() ? get_userdata($ticket->assignedAgentId()) : null;
    $customValues = [];
    foreach ($this->customFields->valuesForTicket($ticket->id()) as $value) {
      $customValues[$value->fieldId()] = $value->value();
    }

    return RestResponse::success([
      'customer' => $customer,
      'information' => [
        'agent' => $agent ? $agent->display_name : null,
        'department' => $department?->name(),
        'category' => $category?->name(),
        'priority' => $ticket->priority()->value,
        'status' => $ticket->status()->value,
        'source' => $ticket->source()->value,
      ],
      'purchase' => $verification ? [
        'provider' => $verification->provider(),
        'reference' => $this->mask($verification->providerReference()),
        'product_id' => $verification->productId(),
        'product_name' => $verification->productName(),
        'license_type' => $verification->licenseType(),
        'purchased_at' => $verification->purchasedAt(),
        'support_expires_at' => $verification->supportExpiresAt(),
        'status' => $verification->status()->value,
      ] : null,
      'activities' => array_map(static fn($activity): array => [
        'id' => $activity->id(),
        'label' => $activity->eventType()->label(),
        'description' => $activity->description(),
        'actor_type' => $activity->actorType()->value,
        'created_at' => $activity->createdAt(),
      ], $this->activities->getByTicket($ticket->id())),
      'attachments' => array_map(fn($attachment): array => $this->attachmentData($attachment), array_filter(
        $this->attachments->findByTicket($ticket->id()),
        static fn($attachment): bool => $attachment->isActive(),
      )),
      'tags' => array_map(static fn($tag): array => $tag->toArray(), $this->tags->forTicket($ticket->id())),
      'custom_fields' => array_map(
        static fn($field): array => [
          'id' => $field->id(),
          'name' => $field->name(),
          'type' => $field->type()->value,
          'options' => $field->options(),
          'is_required' => $field->isRequired(),
          'is_active' => $field->isActive(),
          'value' => $customValues[$field->id()] ?? null,
        ],
        $this->customFields->staffFieldsForTicket($ticket->id()),
      ),
      'options' => [
        'departments' => array_map(static fn($item): array => ['id' => $item->id(), 'name' => $item->name()], $this->departments->active()),
        'categories' => array_map(
          static fn($item): array => [
            'id' => $item->id(),
            'name' => $item->name(),
            'department_id' => $item->departmentId(),
          ],
          $this->categories->applicable($ticket->departmentId()),
        ),
        'tags' => array_map(static fn($tag): array => $tag->toArray(), $this->tags->active()),
        'agents' => array_map(static fn($user): array => ['id' => $user->ID, 'name' => $user->display_name], get_users(['capability' => CapabilityManager::VIEW_TICKETS])),
      ],
    ], 'Ticket context retrieved.');
  }

  public function options(): WP_REST_Response {
    return RestResponse::success([
      'departments' => array_map(static fn($item): array => ['id' => $item->id(), 'name' => $item->name()], $this->departments->active()),
      'categories' => array_map(static fn($item): array => [
        'id' => $item->id(),
        'name' => $item->name(),
        'department_id' => $item->departmentId(),
      ], $this->categories->active()),
      'tags' => array_map(static fn($tag): array => $tag->toArray(), $this->tags->active()),
      'custom_fields' => array_map(static fn($field): array => [
        'id' => $field->id(),
        'name' => $field->name(),
        'type' => $field->type()->value,
        'options' => $field->options(),
        'department_id' => $field->departmentId(),
      ], $this->customFields->active()),
      'agents' => array_map(static fn($user): array => ['id' => $user->ID, 'name' => $user->display_name], get_users(['capability' => CapabilityManager::VIEW_TICKETS])),
    ], 'Ticket queue options retrieved.');
  }

  public function changeTicket(WP_REST_Request $request): WP_REST_Response {
    $id = (int) $request->get_param('id');
    $action = sanitize_key((string) $request->get_param('action'));
    $value = $request->get_param('value');
    $capability = match ($action) {
      'assignment' => 'sbay_assign_ticket', 'department' => 'sbay_move_ticket_department',
      'category' => CapabilityManager::CHANGE_TICKET_CATEGORY,
      'tag_add', 'tag_remove' => CapabilityManager::CHANGE_TICKET_TAGS,
      'custom_field' => CapabilityManager::CHANGE_TICKET_CUSTOM_FIELDS,
      'priority' => 'sbay_change_ticket_priority', 'state' => CapabilityManager::CHANGE_TICKET_STATUS,
      default => '',
    };
    if ($capability === '' || ! current_user_can($capability)) {
      return RestResponse::error('You are not allowed to perform this action.', 'TICKET_ACTION_DENIED', [], 403);
    }

    try {
      $ticket = match ($action) {
        'assignment' => $this->tickets->changeAssignment($id, absint($value) ?: null, get_current_user_id()),
        'department' => $this->tickets->changeDepartment($id, absint($value), get_current_user_id()),
        'category' => $this->tickets->changeCategory($id, absint($value) ?: null, get_current_user_id()),
        'tag_add' => $this->changeTag($id, absint($value), true),
        'tag_remove' => $this->changeTag($id, absint($value), false),
        'custom_field' => $this->changeCustomField($id, (array) $value),
        'priority' => $this->tickets->changePriority($id, TicketPriority::from(sanitize_key((string) $value)), get_current_user_id()),
        'state' => $this->tickets->changeState($id, TicketState::from(sanitize_key((string) $value)), get_current_user_id()),
      };
    } catch (\InvalidArgumentException|\ValueError|RuntimeException $exception) {
      return RestResponse::error($exception->getMessage(), 'TICKET_ACTION_FAILED', [], 422);
    }
    return RestResponse::success($ticket->toArray(), 'Ticket updated.');
  }

  public function bulkChangeTickets(WP_REST_Request $request): WP_REST_Response {
    $ids = array_slice(array_values(array_unique(array_filter(
      array_map('absint', (array) $request->get_param('ticket_ids')),
    ))), 0, 100);
    $action = TicketBulkAction::tryFrom(sanitize_key((string) $request->get_param('action')));
    $value = $request->get_param('value');

    if ($ids === [] || ! $action) {
      return RestResponse::error('Ticket IDs and a supported bulk action are required.', 'TICKET_BULK_ACTION_INVALID', [], 422);
    }

    $capability = match ($action) {
      TicketBulkAction::ASSIGNMENT => 'sbay_reassign_ticket',
      TicketBulkAction::DEPARTMENT => 'sbay_move_ticket_department',
      TicketBulkAction::CATEGORY => CapabilityManager::CHANGE_TICKET_CATEGORY,
      TicketBulkAction::TAG_ADD, TicketBulkAction::TAG_REMOVE => CapabilityManager::CHANGE_TICKET_TAGS,
      TicketBulkAction::CUSTOM_FIELD => CapabilityManager::CHANGE_TICKET_CUSTOM_FIELDS,
      TicketBulkAction::PRIORITY => 'sbay_change_ticket_priority',
      TicketBulkAction::STATE => CapabilityManager::CHANGE_TICKET_STATUS,
    };

    if (! current_user_can($capability)) {
      return RestResponse::error('You are not allowed to perform this bulk action.', 'TICKET_BULK_ACTION_DENIED', [], 403);
    }

    if ($action === TicketBulkAction::CUSTOM_FIELD) {
      $fieldValue = (array) $value;
      $fieldId = absint($fieldValue['field_id'] ?? 0);
      if ($fieldId === 0 || ! array_key_exists('value', $fieldValue)) {
        return RestResponse::error('A custom field and value are required.', 'TICKET_BULK_ACTION_INVALID', [], 422);
      }
      $result = $this->customFields->bulkSetValues(
        $ids,
        $fieldId,
        $fieldValue['value'],
        get_current_user_id(),
      );
    } else {
      $normalizedValue = $action === TicketBulkAction::ASSIGNMENT && $value === 'me'
        ? get_current_user_id()
        : $value;
      $result = $this->tickets->bulkChange($ids, $action, $normalizedValue, get_current_user_id());
    }

    return RestResponse::success([
      'updated' => array_map(static fn($ticket): array => $ticket->toArray(), $result['updated']),
      'failed' => $result['failed'],
    ], 'Bulk ticket action completed.', [
      'requested' => count($ids),
      'updated' => count($result['updated']),
      'failed' => count($result['failed']),
    ]);
  }

  private function changeTag(int $ticketId, int $tagId, bool $attach): \SupportBay\Modules\Tickets\Entities\Ticket {
    if ($attach) { $this->tags->attach($ticketId, $tagId, get_current_user_id()); }
    else { $this->tags->detach($ticketId, $tagId, get_current_user_id()); }
    return $this->tickets->find($ticketId)
      ?? throw new RuntimeException('Ticket was not found.');
  }

  /** @param array<string, mixed> $data */
  private function changeCustomField(int $ticketId, array $data): \SupportBay\Modules\Tickets\Entities\Ticket {
    $this->customFields->setValue(
      $ticketId,
      absint($data['field_id'] ?? 0),
      $data['value'] ?? null,
      get_current_user_id(),
    );
    return $this->tickets->find($ticketId)
      ?? throw new RuntimeException('Ticket was not found.');
  }

  public function mergeTicket(WP_REST_Request $request): WP_REST_Response {
    try {
      $target = $this->ticketMerger->merge(
        (int) $request->get_param('id'),
        absint($request->get_param('target_id')),
        get_current_user_id(),
      );
    } catch (RuntimeException $exception) {
      return RestResponse::error($exception->getMessage(), 'TICKET_MERGE_FAILED', [], 422);
    }

    return RestResponse::success($target->toArray(), 'Tickets merged.');
  }

  public function splitTicket(WP_REST_Request $request): WP_REST_Response {
    try {
      $created = $this->ticketSplitter->split(
        (int) $request->get_param('id'),
        (array) $request->get_param('message_ids'),
        sanitize_text_field(wp_unslash((string) $request->get_param('subject'))),
        get_current_user_id(),
      );
    } catch (\InvalidArgumentException|RuntimeException $exception) {
      return RestResponse::error($exception->getMessage(), 'TICKET_SPLIT_FAILED', [], 422);
    }

    return RestResponse::success($created->toArray(), 'Ticket split.', [], 201);
  }

  public function uploadAttachment(WP_REST_Request $request): WP_REST_Response {
    $ticketId = (int) $request->get_param('ticket_id');
    $message = $this->messages->find((int) $request->get_param('message_id'));
    $file = $request->get_file_params()['file'] ?? null;

    if (! $this->tickets->find($ticketId) || ! $message || $message->ticketId() !== $ticketId || ! is_array($file)) {
      return RestResponse::error('A valid ticket, message, and file are required.', 'ATTACHMENT_INVALID', [], 422);
    }

    try {
      $attachment = $this->attachments->storeUploadedFile($file, [
        'message_id' => $message->id(),
        'ticket_id' => $ticketId,
        'uploaded_by_id' => get_current_user_id(),
        'uploaded_by_type' => AuthorType::AGENT->value,
      ]);
    } catch (\InvalidArgumentException|RuntimeException $exception) {
      return RestResponse::error($exception->getMessage(), 'ATTACHMENT_UPLOAD_FAILED', [], 422);
    }

    return RestResponse::success($this->attachmentData($attachment), 'Attachment uploaded.', [], 201);
  }

  public function downloadAttachment(WP_REST_Request $request): WP_REST_Response {
    $attachment = $this->attachments->find((int) $request->get_param('id'));

    if (! $attachment || ! $attachment->isActive() || ! is_file($attachment->path())) {
      return RestResponse::error('Attachment was not found.', 'ATTACHMENT_NOT_FOUND', [], 404);
    }

    return RestResponse::success(['attachment_id' => $attachment->id()], 'Attachment authorized.');
  }

  public function serveDownload(bool $served, mixed $result, WP_REST_Request $request, WP_REST_Server $server): bool {
    if ($served || ! preg_match('#^/sbay/v1/admin/attachments/(?P<id>\d+)/download$#', $request->get_route(), $matches) || ! $result instanceof WP_REST_Response || $result->get_status() !== 200) {
      return $served;
    }

    $attachment = $this->attachments->find((int) $matches['id']);
    if (! $attachment || ! $attachment->isActive() || ! is_file($attachment->path())) {
      return $served;
    }

    $handle = fopen($attachment->path(), 'rb');
    if ($handle === false) {
      return $served;
    }

    while (ob_get_level() > 0) { ob_end_clean(); }
    $filename = sanitize_file_name($attachment->originalName()) ?: 'attachment.' . $attachment->extension();
    header('Content-Type: ' . $attachment->mimeType());
    header('Content-Length: ' . (string) filesize($attachment->path()));
    header('Content-Disposition: attachment; filename="' . $filename . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
    header('Cache-Control: private, no-store, max-age=0');
    header('X-Content-Type-Options: nosniff');
    fpassthru($handle);
    fclose($handle);
    $this->attachments->recordDownload($attachment->id());
    return true;
  }

  private function attachmentData(object $attachment): array {
    return [
      'id' => $attachment->id(),
      'message_id' => $attachment->messageId(),
      'original_name' => $attachment->originalName(),
      'file_size' => $attachment->fileSize(),
      'mime_type' => $attachment->mimeType(),
    ];
  }

  private function mask(string $reference): string {
    return strlen($reference) <= 8
      ? str_repeat('•', strlen($reference))
      : substr($reference, 0, 4) . '••••' . substr($reference, -4);
  }
}
