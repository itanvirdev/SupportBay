<?php

declare(strict_types=1);

namespace SupportBay\Modules\SavedReplies\Http\Controllers;

use InvalidArgumentException;
use SupportBay\Core\Authorization\CapabilityManager;
use SupportBay\Core\Http\RestResponse;
use SupportBay\Modules\SavedReplies\Entities\SavedReply;
use SupportBay\Modules\SavedReplies\Enums\SavedReplyStatus;
use SupportBay\Modules\SavedReplies\Services\SavedReplyService;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class SavedReplyController {
  public function __construct(private readonly SavedReplyService $replies) {
  }

  public function registerRoutes(): void {
    register_rest_route('sbay/v1', '/saved-replies', ['methods' => 'GET', 'callback' => [$this, 'index'], 'permission_callback' => [$this, 'canUse']]);
    register_rest_route('sbay/v1', '/saved-replies', ['methods' => 'POST', 'callback' => [$this, 'create'], 'permission_callback' => [$this, 'canManage']]);
    register_rest_route('sbay/v1', '/saved-replies/(?P<id>\d+)', ['methods' => 'GET', 'callback' => [$this, 'show'], 'permission_callback' => [$this, 'canUse']]);
    register_rest_route('sbay/v1', '/saved-replies/(?P<id>\d+)', ['methods' => 'PUT', 'callback' => [$this, 'update'], 'permission_callback' => [$this, 'canManage']]);
    register_rest_route('sbay/v1', '/saved-replies/(?P<id>\d+)', ['methods' => 'DELETE', 'callback' => [$this, 'delete'], 'permission_callback' => [$this, 'canManage']]);
    register_rest_route('sbay/v1', '/saved-replies/(?P<id>\d+)/use', ['methods' => 'POST', 'callback' => [$this, 'useReply'], 'permission_callback' => [$this, 'canUse']]);
  }

  public function canUse(): bool|WP_Error { return $this->requires(CapabilityManager::USE_SAVED_REPLIES); }
  public function canManage(): bool|WP_Error { return $this->requires(CapabilityManager::MANAGE_SAVED_REPLIES); }

  public function index(WP_REST_Request $request): WP_REST_Response {
    $statusValue = sanitize_key((string) $request->get_param('status'));
    $status = $statusValue !== '' ? SavedReplyStatus::tryFrom($statusValue) : SavedReplyStatus::ACTIVE;
    if ($statusValue !== '' && ! $status) { return RestResponse::error('Saved reply status is invalid.', 'INVALID_SAVED_REPLY_STATUS', [], 422); }
    if (! current_user_can(CapabilityManager::MANAGE_SAVED_REPLIES)) { $status = SavedReplyStatus::ACTIVE; }
    try {
      $category = $request->has_param('category') ? sanitize_text_field((string) $request->get_param('category')) : null;
      $manage = current_user_can(CapabilityManager::MANAGE_SAVED_REPLIES);
      $scopeDepartment = $request->has_param('department_id') || ! $manage;
      $items = $this->replies->search((string) $request->get_param('search'), $status, sanitize_key((string) $request->get_param('orderby')) ?: 'title', $category, absint($request->get_param('department_id')) ?: null, $scopeDepartment);
    } catch (InvalidArgumentException $exception) {
      return RestResponse::error($exception->getMessage(), 'INVALID_SAVED_REPLY_SORT', [], 422);
    }
    $categories = array_values(array_unique(array_filter(array_map(static fn(SavedReply $reply): ?string => $reply->category(), $this->replies->search('', $status)))));
    sort($categories);
    return RestResponse::success(array_map(static fn(SavedReply $reply): array => $reply->toArray(), $items), 'Saved replies retrieved.', ['total' => count($items), 'categories' => $categories, 'placeholders' => $this->replies->placeholders()]);
  }

  public function show(WP_REST_Request $request): WP_REST_Response {
    $reply = $this->replies->find(absint($request->get_param('id')));
    if ($reply && ! $reply->isActive() && ! current_user_can(CapabilityManager::MANAGE_SAVED_REPLIES)) { $reply = null; }
    return $reply ? RestResponse::success($reply->toArray(), 'Saved reply retrieved.') : RestResponse::error('Saved reply was not found.', 'SAVED_REPLY_NOT_FOUND', [], 404);
  }

  public function create(WP_REST_Request $request): WP_REST_Response {
    try {
      $reply = $this->replies->create((array) $request->get_json_params(), get_current_user_id());
      return RestResponse::success($reply->toArray(), 'Saved reply created.', [], 201);
    } catch (InvalidArgumentException $exception) {
      return RestResponse::error($exception->getMessage(), 'INVALID_SAVED_REPLY', [], 422);
    }
  }

  public function update(WP_REST_Request $request): WP_REST_Response {
    try {
      $reply = $this->replies->update(absint($request->get_param('id')), (array) $request->get_json_params());
      return $reply ? RestResponse::success($reply->toArray(), 'Saved reply updated.') : RestResponse::error('Saved reply was not found.', 'SAVED_REPLY_NOT_FOUND', [], 404);
    } catch (InvalidArgumentException $exception) {
      return RestResponse::error($exception->getMessage(), 'INVALID_SAVED_REPLY', [], 422);
    }
  }

  public function delete(WP_REST_Request $request): WP_REST_Response {
    return $this->replies->delete(absint($request->get_param('id'))) ? RestResponse::success([], 'Saved reply deleted.') : RestResponse::error('Saved reply was not found.', 'SAVED_REPLY_NOT_FOUND', [], 404);
  }

  public function useReply(WP_REST_Request $request): WP_REST_Response {
    $reply = $this->replies->recordUsage(absint($request->get_param('id')), get_current_user_id());
    return $reply ? RestResponse::success($reply->toArray(), 'Saved reply insertion recorded.') : RestResponse::error('Active saved reply was not found.', 'SAVED_REPLY_NOT_FOUND', [], 404);
  }

  private function requires(string $capability): bool|WP_Error {
    if (! is_user_logged_in()) { return new WP_Error('sbay_authentication_required', 'Authentication is required.', ['status' => 401]); }
    return current_user_can($capability) ? true : new WP_Error('sbay_permission_denied', 'You are not allowed to access saved replies.', ['status' => 403]);
  }
}
