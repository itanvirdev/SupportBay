<?php

declare(strict_types=1);

namespace SupportBay\Modules\Customers\Http\Controllers;

use RuntimeException;
use SupportBay\Core\Authorization\CapabilityManager;
use SupportBay\Core\Http\RestResponse;
use SupportBay\Modules\Customers\Entities\Customer;
use SupportBay\Modules\Customers\Enums\CustomerSource;
use SupportBay\Modules\Customers\Enums\CustomerState;
use SupportBay\Modules\Customers\Services\CustomerService;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class CustomerController {
  public function __construct(private readonly CustomerService $customers) {
  }

  public function registerRoutes(): void {
    register_rest_route('sbay/v1', '/customers', [
      'methods' => 'GET', 'callback' => [$this, 'index'],
      'permission_callback' => [$this, 'permissions'],
    ]);
    register_rest_route('sbay/v1', '/customers/(?P<id>\d+)', [
      'methods' => 'GET', 'callback' => [$this, 'show'],
      'permission_callback' => [$this, 'permissions'],
    ]);
    register_rest_route('sbay/v1', '/customers/(?P<id>\d+)/state', [
      'methods' => 'POST', 'callback' => [$this, 'updateState'],
      'permission_callback' => [$this, 'permissions'],
    ]);
  }

  public function permissions(): bool|WP_Error {
    if (! is_user_logged_in()) {
      return new WP_Error('sbay_authentication_required', 'Authentication is required.', ['status' => 401]);
    }

    return current_user_can(CapabilityManager::MANAGE_CUSTOMERS)
      ? true
      : new WP_Error('sbay_permission_denied', 'You are not allowed to manage customers.', ['status' => 403]);
  }

  public function updateState(WP_REST_Request $request): WP_REST_Response {
    $customer = $this->customers->find(absint($request->get_param('id')));
    $state = CustomerState::tryFrom(sanitize_key((string) $request->get_param('state')));

    if (! $customer) {
      return RestResponse::error('Customer was not found.', 'CUSTOMER_NOT_FOUND', [], 404);
    }

    if (! $state) {
      return RestResponse::error('Invalid customer state.', 'INVALID_CUSTOMER_STATE', [], 422);
    }

    match ($state) {
      CustomerState::REGISTERED => $this->customers->register($customer->id()),
      CustomerState::VERIFIED => $this->customers->verify($customer->id()),
      CustomerState::SUSPENDED => $this->customers->suspend($customer->id()),
      CustomerState::GUEST => $this->customers->update($customer->id(), ['state' => $state->value]),
    };

    return RestResponse::success(
      $this->data($this->customers->find($customer->id())),
      'Customer state updated.',
    );
  }

  public function index(WP_REST_Request $request): WP_REST_Response {
    $state = CustomerState::tryFrom(sanitize_key((string) $request->get_param('state')));
    $source = CustomerSource::tryFrom(sanitize_key((string) $request->get_param('source')));
    $items = $state
      ? $this->customers->getByState($state)
      : ($source ? $this->customers->getBySource($source) : $this->customers->all());

    return $this->collection($items, $request);
  }

  public function show(WP_REST_Request $request): WP_REST_Response {
    $customer = $this->customers->find(absint($request->get_param('id')));

    return $customer
      ? RestResponse::success($this->data($customer), 'Customer retrieved.')
      : RestResponse::error('Customer was not found.', 'CUSTOMER_NOT_FOUND', [], 404);
  }

  /** @param Customer[] $items */
  private function collection(array $items, WP_REST_Request $request): WP_REST_Response {
    $page = max(1, absint($request->get_param('page')) ?: 1);
    $perPage = min(100, max(1, absint($request->get_param('per_page')) ?: 20));
    $total = count($items);
    $items = array_slice($items, ($page - 1) * $perPage, $perPage);

    return RestResponse::success(
      array_map(fn(Customer $customer): array => $this->data($customer), $items),
      'Customers retrieved.',
      ['page' => $page, 'per_page' => $perPage, 'total' => $total, 'total_pages' => (int) ceil($total / $perPage)],
    );
  }

  /** @return array<string, mixed> */
  private function data(Customer $customer): array {
    $data = $customer->toArray();

    try {
      $profile = $this->customers->profile($customer->id());
      $data['display_name'] = $profile->displayName();
      $data['email'] = $profile->email();
    } catch (RuntimeException) {
      $data['display_name'] = null;
      $data['email'] = null;
    }

    return $data;
  }
}
