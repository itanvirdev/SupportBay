<?php

declare(strict_types=1);

namespace SupportBay\Modules\Providers\Http\Controllers;

use SupportBay\Core\Http\RestResponse;
use SupportBay\Modules\Providers\Entities\Provider;
use SupportBay\Modules\Providers\Enums\ProviderCategory;
use SupportBay\Modules\Providers\Enums\ProviderStatus;
use SupportBay\Modules\Providers\Services\ProviderService;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class ProviderController {
  public function __construct(private readonly ProviderService $providers) {
  }

  public function registerRoutes(): void {
    register_rest_route('sbay/v1', '/providers', [
      'methods' => 'GET', 'callback' => [$this, 'index'],
      'permission_callback' => [$this, 'permissions'],
    ]);
    register_rest_route('sbay/v1', '/providers/(?P<id>\d+)', [
      'methods' => 'GET', 'callback' => [$this, 'show'],
      'permission_callback' => [$this, 'permissions'],
    ]);
  }

  public function permissions(): bool|WP_Error {
    if (! is_user_logged_in()) {
      return new WP_Error('sbay_authentication_required', 'Authentication is required.', ['status' => 401]);
    }

    return current_user_can('manage_options')
      ? true
      : new WP_Error('sbay_permission_denied', 'You are not allowed to manage providers.', ['status' => 403]);
  }

  public function index(WP_REST_Request $request): WP_REST_Response {
    $category = ProviderCategory::tryFrom(sanitize_key((string) $request->get_param('category')));
    $status = ProviderStatus::tryFrom(sanitize_key((string) $request->get_param('status')));
    $items = array_values(array_filter(
      $this->providers->all(),
      static fn(Provider $provider): bool =>
        (! $category || $provider->category() === $category)
        && (! $status || $provider->status() === $status),
    ));

    return RestResponse::success(
      array_map(fn(Provider $provider): array => $this->data($provider), $items),
      'Providers retrieved.',
      ['total' => count($items)],
    );
  }

  public function show(WP_REST_Request $request): WP_REST_Response {
    $provider = $this->providers->find(absint($request->get_param('id')));

    return $provider
      ? RestResponse::success($this->data($provider), 'Provider retrieved.')
      : RestResponse::error('Provider was not found.', 'PROVIDER_NOT_FOUND', [], 404);
  }

  /** @return array<string, mixed> */
  private function data(Provider $provider): array {
    return [
      'id' => $provider->id(), 'slug' => $provider->slug(),
      'name' => $provider->name(), 'category' => $provider->category()->value,
      'version' => $provider->version(), 'status' => $provider->status()->value,
      'configured' => $provider->hasSettings(),
      'last_connected_at' => $provider->lastConnectedAt(),
      'has_error' => $provider->hasError(),
      'created_at' => $provider->createdAt(), 'updated_at' => $provider->updatedAt(),
    ];
  }
}
