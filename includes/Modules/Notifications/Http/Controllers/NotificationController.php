<?php

declare(strict_types=1);

namespace SupportBay\Modules\Notifications\Http\Controllers;

use RuntimeException;
use SupportBay\Core\Authorization\CapabilityManager;
use SupportBay\Core\Http\RestResponse;
use SupportBay\Modules\Notifications\Data\NotificationLogQuery;
use SupportBay\Modules\Notifications\Entities\NotificationLog;
use SupportBay\Modules\Notifications\Enums\NotificationStatus;
use SupportBay\Modules\Notifications\Services\NotificationService;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class NotificationController {
  public function __construct(
    private readonly NotificationService $notifications,
  ) {
  }

  public function registerRoutes(): void {
    register_rest_route('sbay/v1', '/admin/notifications', [
      'methods' => 'GET',
      'callback' => [$this, 'index'],
      'permission_callback' => [$this, 'permissions'],
    ]);
    register_rest_route('sbay/v1', '/admin/notifications/(?P<id>\d+)', [
      'methods' => 'GET',
      'callback' => [$this, 'show'],
      'permission_callback' => [$this, 'permissions'],
    ]);
    register_rest_route('sbay/v1', '/admin/notifications/(?P<id>\d+)/retry', [
      'methods' => 'POST',
      'callback' => [$this, 'retry'],
      'permission_callback' => [$this, 'permissions'],
    ]);
  }

  public function permissions(): bool|WP_Error {
    if (! is_user_logged_in()) {
      return new WP_Error(
        'sbay_authentication_required',
        'Authentication is required.',
        ['status' => 401],
      );
    }

    return current_user_can(CapabilityManager::MANAGE_SETTINGS)
      ? true
      : new WP_Error(
        'sbay_permission_denied',
        'You are not allowed to manage notification logs.',
        ['status' => 403],
      );
  }

  public function index(WP_REST_Request $request): WP_REST_Response {
    $page = max(1, absint($request->get_param('page')) ?: 1);
    $perPage = min(100, max(
      1,
      absint($request->get_param('per_page')) ?: 20,
    ));
    $status = NotificationStatus::tryFrom(
      sanitize_key((string) $request->get_param('status'))
    );
    $result = $this->notifications->searchLogs(
      new NotificationLogQuery(
        page: $page,
        perPage: $perPage,
        search: sanitize_text_field(
          wp_unslash((string) $request->get_param('search'))
        ) ?: null,
        channel: sanitize_key(
          (string) $request->get_param('channel')
        ) ?: null,
        event: sanitize_key(
          (string) $request->get_param('event')
        ) ?: null,
        status: $status?->value,
        orderBy: sanitize_key(
          (string) $request->get_param('orderby')
        ) ?: 'created_at',
        direction: sanitize_key(
          (string) $request->get_param('order')
        ) ?: 'desc',
      )
    );

    return RestResponse::success(
      array_map(
        fn(NotificationLog $log): array => $this->data($log),
        $result['items'],
      ),
      'Notification logs retrieved.',
      [
        'page' => $page,
        'per_page' => $perPage,
        'total' => $result['total'],
        'total_pages' => (int) ceil($result['total'] / $perPage),
        'statuses' => array_map(
          static fn(NotificationStatus $status): string => $status->value,
          NotificationStatus::cases(),
        ),
      ],
    );
  }

  public function show(WP_REST_Request $request): WP_REST_Response {
    $log = $this->notifications->findLog(
      absint($request->get_param('id'))
    );

    return $log
      ? RestResponse::success(
        $this->data($log),
        'Notification log retrieved.',
      )
      : RestResponse::error(
        'Notification log was not found.',
        'NOTIFICATION_LOG_NOT_FOUND',
        [],
        404,
      );
  }

  public function retry(WP_REST_Request $request): WP_REST_Response {
    $logId = absint($request->get_param('id'));

    if (! $this->notifications->findLog($logId)) {
      return RestResponse::error(
        'Notification log was not found.',
        'NOTIFICATION_LOG_NOT_FOUND',
        [],
        404,
      );
    }

    try {
      $sent = $this->notifications->retry($logId);
    } catch (RuntimeException $exception) {
      return RestResponse::error(
        $exception->getMessage(),
        'NOTIFICATION_RETRY_FAILED',
        [],
        409,
      );
    }

    $log = $this->notifications->findLog($logId);

    return $sent && $log
      ? RestResponse::success(
        $this->data($log),
        'Notification sent successfully.',
      )
      : RestResponse::error(
        $log?->errorMessage() ?? 'Notification delivery failed.',
        'NOTIFICATION_RETRY_FAILED',
        [],
        502,
      );
  }

  /** @return array<string, mixed> */
  private function data(NotificationLog $log): array {
    return [
      'id' => $log->id(),
      'ticket_id' => $log->ticketId(),
      'user_id' => $log->userId(),
      'channel' => $log->channel(),
      'event' => $log->event(),
      'recipient' => $log->recipient(),
      'subject' => $log->subject(),
      'status' => $log->status()->value,
      'provider' => $log->provider(),
      'provider_message_id' => $log->providerMessageId(),
      'error_message' => $log->errorMessage(),
      'retry_count' => $log->retryCount(),
      'can_retry' => $log->canRetry(),
      'scheduled_at' => $log->scheduledAt(),
      'sent_at' => $log->sentAt(),
      'delivered_at' => $log->deliveredAt(),
      'created_at' => $log->createdAt(),
      'updated_at' => $log->updatedAt(),
    ];
  }
}
