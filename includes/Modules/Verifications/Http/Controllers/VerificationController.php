<?php

declare(strict_types=1);

namespace SupportBay\Modules\Verifications\Http\Controllers;

use SupportBay\Core\Http\RestResponse;
use SupportBay\Core\Authorization\CapabilityManager;
use SupportBay\Modules\Verifications\Entities\Verification;
use SupportBay\Modules\Verifications\Enums\VerificationStatus;
use SupportBay\Modules\Verifications\Services\VerificationService;
use SupportBay\Modules\Verifications\Data\VerificationDirectoryItem;
use SupportBay\Modules\Verifications\Data\VerificationDirectoryQuery;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class VerificationController {
  public function __construct(private readonly VerificationService $verifications) {
  }

  public function registerRoutes(): void {
    register_rest_route('sbay/v1', '/verifications', [
      'methods' => 'GET', 'callback' => [$this, 'index'],
      'permission_callback' => [$this, 'permissions'],
    ]);
    register_rest_route('sbay/v1', '/verifications/(?P<id>\d+)', [
      'methods' => 'GET', 'callback' => [$this, 'show'],
      'permission_callback' => [$this, 'permissions'],
    ]);
    foreach (['refresh', 'revoke'] as $action) {
      register_rest_route('sbay/v1', '/verifications/(?P<id>\d+)/' . $action, [
        'methods' => 'POST', 'callback' => [$this, $action],
        'permission_callback' => [$this, 'canRefresh'],
      ]);
    }
  }

  public function permissions(): bool|WP_Error {
    if (! is_user_logged_in()) {
      return new WP_Error('sbay_authentication_required', 'Authentication is required.', ['status' => 401]);
    }

    return current_user_can(CapabilityManager::VIEW_VERIFICATIONS)
      ? true
      : new WP_Error('sbay_permission_denied', 'You are not allowed to view purchase verifications.', ['status' => 403]);
  }

  public function canRefresh(): bool|WP_Error {
    if (! is_user_logged_in()) {
      return new WP_Error('sbay_authentication_required', 'Authentication is required.', ['status' => 401]);
    }

    return current_user_can(CapabilityManager::REFRESH_VERIFICATION)
      ? true
      : new WP_Error('sbay_permission_denied', 'You are not allowed to modify purchase verifications.', ['status' => 403]);
  }

  public function refresh(WP_REST_Request $request): WP_REST_Response {
    try {
      $verification = $this->verifications->refreshPurchase(
        absint($request->get_param('id')),
      );
    } catch (\RuntimeException $exception) {
      return RestResponse::error($exception->getMessage(), 'VERIFICATION_REFRESH_FAILED', [], 409);
    }

    return RestResponse::success($this->data($verification), 'Purchase verification refreshed.');
  }

  public function revoke(WP_REST_Request $request): WP_REST_Response {
    try {
      $verification = $this->verifications->revoke(absint($request->get_param('id')));
    } catch (\RuntimeException $exception) {
      return RestResponse::error($exception->getMessage(), 'VERIFICATION_REVOKE_FAILED', [], 404);
    }

    return RestResponse::success($this->data($verification), 'Purchase verification revoked.');
  }

  public function index(WP_REST_Request $request): WP_REST_Response {
    $page = max(1, absint($request->get_param('page')) ?: 1);
    $perPage = min(100, max(1, absint($request->get_param('per_page')) ?: 20));
    $status = VerificationStatus::tryFrom(sanitize_key((string) $request->get_param('status')));
    $provider = sanitize_key((string) $request->get_param('provider')) ?: null;
    $result = $this->verifications->search(new VerificationDirectoryQuery(
      page: $page,
      perPage: $perPage,
      search: sanitize_text_field(wp_unslash((string) $request->get_param('search'))) ?: null,
      provider: $provider,
      status: $status?->value,
      orderBy: sanitize_key((string) $request->get_param('orderby')) ?: 'updated_at',
      direction: sanitize_key((string) $request->get_param('order')) ?: 'desc',
    ));

    return RestResponse::success(
      array_map(static fn(VerificationDirectoryItem $item): array => $item->toArray(), $result['items']),
      'Purchase verifications retrieved.',
      [
        'page' => $page,
        'per_page' => $perPage,
        'total' => $result['total'],
        'total_pages' => (int) ceil($result['total'] / $perPage),
        'providers' => $this->verifications->providerSlugs(),
        'statuses' => VerificationStatus::values(),
      ],
    );
  }

  public function show(WP_REST_Request $request): WP_REST_Response {
    $verification = $this->verifications->find(absint($request->get_param('id')));

    return $verification
      ? RestResponse::success($this->data($verification), 'Purchase verification retrieved.')
      : RestResponse::error('Purchase verification was not found.', 'VERIFICATION_NOT_FOUND', [], 404);
  }

  /** @return array<string, mixed> */
  private function data(Verification $verification): array {
    return [
      'id' => $verification->id(),
      'provider' => $verification->provider(),
      'reference' => VerificationDirectoryItem::mask($verification->providerReference()),
      'customer_id' => $verification->customerId(),
      'provider_customer_reference' => $verification->providerCustomerReference(),
      'product_id' => $verification->productId(),
      'product_name' => $verification->productName(),
      'license_type' => $verification->licenseType(),
      'support_expires_at' => $verification->supportExpiresAt(),
      'purchased_at' => $verification->purchasedAt(),
      'verified_at' => $verification->verifiedAt(),
      'last_checked_at' => $verification->lastCheckedAt(),
      'verification_status' => $verification->status()->value,
      'has_snapshot' => $verification->hasSnapshot(),
      'can_refresh' => $verification->canRefresh(),
      'created_at' => $verification->createdAt(),
      'updated_at' => $verification->updatedAt(),
    ];
  }
}
