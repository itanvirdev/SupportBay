<?php

declare(strict_types=1);

namespace SupportBay\Modules\Providers\Http\Controllers;

use SupportBay\Core\Http\RestResponse;
use SupportBay\Core\Authorization\CapabilityManager;
use SupportBay\Modules\Providers\Entities\Provider;
use SupportBay\Modules\Providers\Enums\ProviderCategory;
use SupportBay\Modules\Providers\Enums\ProviderStatus;
use SupportBay\Modules\Providers\Services\ProviderService;
use SupportBay\Modules\Providers\Services\ProviderConfiguration;
use SupportBay\Modules\Providers\Services\ProviderConnectionService;
use InvalidArgumentException;
use RuntimeException;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class ProviderController {
  public function __construct(
    private readonly ProviderService $providers,
    private readonly ProviderConfiguration $configuration,
    private readonly ProviderConnectionService $connections,
  ) {
  }

  public function registerRoutes(): void {
    register_rest_route('sbay/v1', '/providers', [
      'methods' => 'GET', 'callback' => [$this, 'index'],
      'permission_callback' => [$this, 'permissions'],
    ]);
    register_rest_route('sbay/v1', '/providers/(?P<id>\d+)', [
      [
        'methods' => 'GET', 'callback' => [$this, 'show'],
        'permission_callback' => [$this, 'permissions'],
      ],
      [
        'methods' => 'PUT', 'callback' => [$this, 'update'],
        'permission_callback' => [$this, 'permissions'],
      ],
    ]);
    foreach (['enable', 'disable'] as $action) {
      register_rest_route('sbay/v1', '/providers/(?P<id>\d+)/' . $action, [
        'methods' => 'POST', 'callback' => [$this, $action],
        'permission_callback' => [$this, 'permissions'],
      ]);
    }
    register_rest_route('sbay/v1', '/providers/(?P<id>\d+)/configuration', [
      [
        'methods' => 'GET', 'callback' => [$this, 'configuration'],
        'permission_callback' => [$this, 'permissions'],
      ],
      [
        'methods' => 'PUT', 'callback' => [$this, 'updateConfiguration'],
        'permission_callback' => [$this, 'permissions'],
      ],
    ]);
    register_rest_route('sbay/v1', '/providers/(?P<id>\d+)/test-connection', [
      'methods' => 'POST', 'callback' => [$this, 'testConnection'],
      'permission_callback' => [$this, 'permissions'],
    ]);
  }

  public function permissions(): bool|WP_Error {
    if (! is_user_logged_in()) {
      return new WP_Error('sbay_authentication_required', 'Authentication is required.', ['status' => 401]);
    }

    return current_user_can(CapabilityManager::MANAGE_PROVIDERS)
      ? true
      : new WP_Error('sbay_permission_denied', 'You are not allowed to manage providers.', ['status' => 403]);
  }

  public function enable(WP_REST_Request $request): WP_REST_Response {
    return $this->transition($request, 'enable');
  }

  public function disable(WP_REST_Request $request): WP_REST_Response {
    return $this->transition($request, 'disable');
  }

  private function transition(WP_REST_Request $request, string $action): WP_REST_Response {
    $id = absint($request->get_param('id'));

    if (! $this->providers->find($id)) {
      return RestResponse::error('Provider was not found.', 'PROVIDER_NOT_FOUND', [], 404);
    }

    $this->providers->{$action}($id);

    return RestResponse::success(
      $this->data($this->providers->find($id)),
      'Provider status updated.',
    );
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

  public function update(WP_REST_Request $request): WP_REST_Response {
    $id = absint($request->get_param('id'));

    try {
      $provider = $this->providers->rename(
        $id,
        sanitize_text_field(wp_unslash((string) $request->get_param('name'))),
      );
    } catch (InvalidArgumentException|RuntimeException $exception) {
      return RestResponse::error($exception->getMessage(), 'PROVIDER_UPDATE_FAILED', [], 422);
    }

    return RestResponse::success(
      $this->data($provider),
      'Provider ticket-form label updated.',
    );
  }

  public function configuration(WP_REST_Request $request): WP_REST_Response {
    $provider = $this->providers->find(absint($request->get_param('id')));

    if (! $provider) {
      return RestResponse::error('Provider was not found.', 'PROVIDER_NOT_FOUND', [], 404);
    }

    try {
      return RestResponse::success(
        $this->configuration->form($provider->slug()),
        'Provider configuration retrieved.',
      );
    } catch (RuntimeException $exception) {
      return RestResponse::error($exception->getMessage(), 'PROVIDER_NOT_CONFIGURABLE', [], 422);
    }
  }

  public function updateConfiguration(WP_REST_Request $request): WP_REST_Response {
    $provider = $this->providers->find(absint($request->get_param('id')));

    if (! $provider) {
      return RestResponse::error('Provider was not found.', 'PROVIDER_NOT_FOUND', [], 404);
    }

    $settings = $request->get_param('settings');

    if (! is_array($settings)) {
      return RestResponse::error('Provider settings must be an object.', 'INVALID_PROVIDER_SETTINGS', [], 422);
    }

    try {
      $this->configuration->update($provider->slug(), $settings);

      return RestResponse::success(
        $this->configuration->form($provider->slug()),
        'Provider configuration saved.',
      );
    } catch (InvalidArgumentException|RuntimeException $exception) {
      return RestResponse::error($exception->getMessage(), 'INVALID_PROVIDER_SETTINGS', [], 422);
    }
  }

  public function testConnection(WP_REST_Request $request): WP_REST_Response {
    $provider = $this->providers->find(absint($request->get_param('id')));

    if (! $provider) {
      return RestResponse::error('Provider was not found.', 'PROVIDER_NOT_FOUND', [], 404);
    }

    try {
      $result = $this->connections->test($provider->slug());

      return RestResponse::success(
        [
          'test' => $result->toArray(),
          'provider' => $this->data($this->providers->find($provider->id())),
        ],
        $result->message(),
      );
    } catch (RuntimeException $exception) {
      return RestResponse::error($exception->getMessage(), 'PROVIDER_CONNECTION_TEST_UNAVAILABLE', [], 422);
    }
  }

  /** @return array<string, mixed> */
  private function data(Provider $provider): array {
    return [
      'id' => $provider->id(), 'slug' => $provider->slug(),
      'name' => $provider->name(), 'category' => $provider->category()->value,
      'version' => $provider->version(), 'status' => $provider->status()->value,
      'configured' => $this->configuration->configured($provider->slug()),
      'connection_test_available' => $this->connections->supports($provider->slug()),
      'last_connected_at' => $provider->lastConnectedAt(),
      'has_error' => $provider->hasError(),
      'created_at' => $provider->createdAt(), 'updated_at' => $provider->updatedAt(),
    ];
  }
}
