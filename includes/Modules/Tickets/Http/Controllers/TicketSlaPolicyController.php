<?php

declare(strict_types=1);

namespace SupportBay\Modules\Tickets\Http\Controllers;

use InvalidArgumentException;
use SupportBay\Core\Authorization\CapabilityManager;
use SupportBay\Core\Http\RestResponse;
use SupportBay\Modules\Tickets\Services\TicketSlaPolicyService;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class TicketSlaPolicyController {
  private const ROUTE = '/admin/ticket-sla-policy';

  public function __construct(private readonly TicketSlaPolicyService $policies) {
  }

  public function registerRoutes(): void {
    register_rest_route('sbay/v1', self::ROUTE, [[
      'methods' => 'GET', 'callback' => [$this, 'show'], 'permission_callback' => [$this, 'permissions'],
    ], [
      'methods' => 'PUT', 'callback' => [$this, 'update'], 'permission_callback' => [$this, 'permissions'],
    ]]);
  }

  public function permissions(): bool|WP_Error {
    if (! is_user_logged_in()) { return new WP_Error('sbay_authentication_required', 'Authentication is required.', ['status' => 401]); }
    return current_user_can(CapabilityManager::MANAGE_SETTINGS) ? true
      : new WP_Error('sbay_permission_denied', 'You are not allowed to manage ticket SLA settings.', ['status' => 403]);
  }

  public function show(WP_REST_Request $request): WP_REST_Response {
    return RestResponse::success($this->policies->get()->toArray(), 'Ticket SLA policy retrieved.');
  }

  public function update(WP_REST_Request $request): WP_REST_Response {
    try {
      return RestResponse::success($this->policies->update((array) $request->get_json_params())->toArray(), 'Ticket SLA policy saved.');
    } catch (InvalidArgumentException $exception) {
      return RestResponse::error($exception->getMessage(), 'INVALID_TICKET_SLA_POLICY', [], 422);
    }
  }
}
