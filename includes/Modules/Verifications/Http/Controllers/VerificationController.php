<?php

declare(strict_types=1);

namespace SupportBay\Modules\Verifications\Http\Controllers;

use SupportBay\Core\Http\RestResponse;
use SupportBay\Modules\Verifications\Entities\Verification;
use SupportBay\Modules\Verifications\Enums\VerificationStatus;
use SupportBay\Modules\Verifications\Services\VerificationService;
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
  }

  public function permissions(): bool|WP_Error {
    if (! is_user_logged_in()) {
      return new WP_Error('sbay_authentication_required', 'Authentication is required.', ['status' => 401]);
    }

    return current_user_can('manage_options')
      ? true
      : new WP_Error('sbay_permission_denied', 'You are not allowed to view purchase verifications.', ['status' => 403]);
  }

  public function index(WP_REST_Request $request): WP_REST_Response {
    $status = VerificationStatus::tryFrom(sanitize_key((string) $request->get_param('status')));
    $provider = sanitize_key((string) $request->get_param('provider'));
    $items = $status
      ? $this->verifications->findByStatus($status)
      : ($provider !== '' ? $this->verifications->findByProvider($provider) : $this->verifications->all());
    $page = max(1, absint($request->get_param('page')) ?: 1);
    $perPage = min(100, max(1, absint($request->get_param('per_page')) ?: 20));
    $total = count($items);
    $items = array_slice($items, ($page - 1) * $perPage, $perPage);

    return RestResponse::success(
      array_map(static fn(Verification $verification): array => $verification->toArray(), $items),
      'Purchase verifications retrieved.',
      ['page' => $page, 'per_page' => $perPage, 'total' => $total, 'total_pages' => (int) ceil($total / $perPage)],
    );
  }

  public function show(WP_REST_Request $request): WP_REST_Response {
    $verification = $this->verifications->find(absint($request->get_param('id')));

    return $verification
      ? RestResponse::success($verification->toArray(), 'Purchase verification retrieved.')
      : RestResponse::error('Purchase verification was not found.', 'VERIFICATION_NOT_FOUND', [], 404);
  }
}
