<?php

declare(strict_types=1);

namespace SupportBay\Modules\Tags\Http\Controllers;

use InvalidArgumentException;
use SupportBay\Core\Authorization\CapabilityManager;
use SupportBay\Core\Http\RestResponse;
use SupportBay\Modules\Tags\Entities\Tag;
use SupportBay\Modules\Tags\Services\TagService;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class TagController {
  public function __construct(private readonly TagService $tags) {
  }

  public function registerRoutes(): void {
    register_rest_route('sbay/v1', '/tags', [
      'methods' => 'GET', 'callback' => [$this, 'index'],
      'permission_callback' => [$this, 'canView'],
    ]);
    register_rest_route('sbay/v1', '/tags', [
      'methods' => 'POST', 'callback' => [$this, 'create'],
      'permission_callback' => [$this, 'canManage'],
    ]);
    register_rest_route('sbay/v1', '/tags/bulk', [
      'methods' => 'POST', 'callback' => [$this, 'bulk'],
      'permission_callback' => [$this, 'canManage'],
    ]);
    foreach (['GET' => 'show', 'PUT' => 'update', 'DELETE' => 'delete'] as $method => $callback) {
      register_rest_route('sbay/v1', '/tags/(?P<id>\d+)', [
        'methods' => $method,
        'callback' => [$this, $callback],
        'permission_callback' => [$this, $method === 'GET' ? 'canView' : 'canManage'],
        'args' => ['id' => ['sanitize_callback' => 'absint']],
      ]);
    }
  }

  public function canView(): bool|WP_Error {
    return $this->requires(CapabilityManager::VIEW_TICKETS);
  }

  public function canManage(): bool|WP_Error {
    return $this->requires(CapabilityManager::MANAGE_TAGS);
  }

  public function index(WP_REST_Request $request): WP_REST_Response {
    $items = $request->get_param('status') === 'active'
      ? $this->tags->active()
      : $this->tags->all();
    return RestResponse::success(
      array_map(static fn(Tag $tag): array => $tag->toArray(), $items),
      'Tags retrieved.',
      ['total' => count($items)],
    );
  }

  public function show(WP_REST_Request $request): WP_REST_Response {
    $tag = $this->tags->find(absint($request['id']));
    return $tag
      ? RestResponse::success($tag->toArray(), 'Tag retrieved.')
      : RestResponse::error('Tag was not found.', 'TAG_NOT_FOUND', [], 404);
  }

  public function create(WP_REST_Request $request): WP_REST_Response {
    return $this->mutate(
      fn(): Tag => $this->tags->create((array) $request->get_json_params()),
      201,
    );
  }

  public function update(WP_REST_Request $request): WP_REST_Response {
    return $this->mutate(fn(): ?Tag => $this->tags->update(
      absint($request['id']),
      (array) $request->get_json_params(),
    ));
  }

  public function bulk(WP_REST_Request $request): WP_REST_Response {
    try {
      $tags = $this->tags->bulkUpsert((array) $request->get_param('items'));
      return RestResponse::success(
        array_map(static fn(Tag $tag): array => $tag->toArray(), $tags),
        'Tags saved.',
        ['total' => count($tags)],
      );
    } catch (InvalidArgumentException $exception) {
      return RestResponse::error($exception->getMessage(), 'INVALID_TAGS', [], 422);
    }
  }

  public function delete(WP_REST_Request $request): WP_REST_Response {
    try {
      $deleted = $this->tags->delete(absint($request['id']));
    } catch (InvalidArgumentException $exception) {
      return RestResponse::error($exception->getMessage(), 'TAG_IN_USE', [], 409);
    }
    return $deleted
      ? RestResponse::success([], 'Tag deleted.')
      : RestResponse::error('Tag was not found.', 'TAG_NOT_FOUND', [], 404);
  }

  private function mutate(callable $callback, int $status = 200): WP_REST_Response {
    try {
      $tag = $callback();
      return $tag
        ? RestResponse::success($tag->toArray(), 'Tag saved.', [], $status)
        : RestResponse::error('Tag was not found.', 'TAG_NOT_FOUND', [], 404);
    } catch (InvalidArgumentException $exception) {
      return RestResponse::error($exception->getMessage(), 'INVALID_TAG', [], 422);
    }
  }

  private function requires(string $capability): bool|WP_Error {
    if (! is_user_logged_in()) {
      return new WP_Error('sbay_authentication_required', 'Authentication is required.', ['status' => 401]);
    }
    return current_user_can($capability)
      ? true
      : new WP_Error('sbay_permission_denied', 'You are not allowed to access tags.', ['status' => 403]);
  }
}
