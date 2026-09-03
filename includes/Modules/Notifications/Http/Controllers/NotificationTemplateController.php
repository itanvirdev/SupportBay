<?php

declare(strict_types=1);

namespace SupportBay\Modules\Notifications\Http\Controllers;

use InvalidArgumentException;
use SupportBay\Core\Authorization\CapabilityManager;
use SupportBay\Core\Http\RestResponse;
use SupportBay\Modules\Notifications\Data\NotificationData;
use SupportBay\Modules\Notifications\Entities\NotificationTemplate;
use SupportBay\Modules\Notifications\Enums\NotificationRecipientType;
use SupportBay\Modules\Notifications\Enums\NotificationTemplateStatus;
use SupportBay\Modules\Notifications\Services\NotificationTemplateService;
use SupportBay\Modules\Notifications\Services\NotificationService;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class NotificationTemplateController {
  private const ROUTE = '/admin/notification-templates';

  public function __construct(
    private readonly NotificationTemplateService $templates,
    private readonly NotificationService $notifications,
  ) {
  }

  public function registerRoutes(): void {
    register_rest_route('sbay/v1', self::ROUTE, [
      'methods' => 'GET',
      'callback' => [$this, 'index'],
      'permission_callback' => [$this, 'permissions'],
    ]);
    register_rest_route(
      'sbay/v1',
      self::ROUTE . '/(?P<event>[a-z0-9_-]+)/(?P<recipient>[a-z]+)',
      [
        [
          'methods' => 'GET',
          'callback' => [$this, 'show'],
          'permission_callback' => [$this, 'permissions'],
        ],
        [
          'methods' => 'PUT',
          'callback' => [$this, 'update'],
          'permission_callback' => [$this, 'permissions'],
        ],
      ],
    );
    foreach (['preview', 'test-email'] as $action) {
      register_rest_route(
        'sbay/v1',
        self::ROUTE
          . '/(?P<event>[a-z0-9_-]+)/(?P<recipient>[a-z]+)/' . $action,
        [
          'methods' => 'POST',
          'callback' => [$this, $action === 'test-email' ? 'testEmail' : $action],
          'permission_callback' => [$this, 'permissions'],
        ],
      );
    }
    register_rest_route(
      'sbay/v1',
      self::ROUTE . '/(?P<event>[a-z0-9_-]+)/(?P<recipient>[a-z]+)/reset',
      [
        'methods' => 'POST',
        'callback' => [$this, 'reset'],
        'permission_callback' => [$this, 'permissions'],
      ],
    );
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
        'You are not allowed to manage notification templates.',
        ['status' => 403],
      );
  }

  public function index(WP_REST_Request $request): WP_REST_Response {
    $templates = $this->templates->all();

    return RestResponse::success(
      array_map(
        fn(NotificationTemplate $template): array => $this->data($template),
        $templates,
      ),
      'Notification templates retrieved.',
      [
        'total' => count($templates),
        'statuses' => array_column(
          array_map(
            static fn(NotificationTemplateStatus $status): array => [
              'value' => $status->value,
            ],
            NotificationTemplateStatus::cases(),
          ),
          'value',
        ),
        'recipient_types' => array_column(
          array_map(
            static fn(NotificationRecipientType $type): array => [
              'value' => $type->value,
            ],
            NotificationRecipientType::cases(),
          ),
          'value',
        ),
        'placeholders' => $this->placeholders(),
      ],
    );
  }

  public function show(WP_REST_Request $request): WP_REST_Response {
    $template = $this->template($request);

    return $template
      ? RestResponse::success(
        $this->data($template),
        'Notification template retrieved.',
        ['placeholders' => $this->placeholders()],
      )
      : $this->notFound();
  }

  public function update(WP_REST_Request $request): WP_REST_Response {
    $recipientType = $this->recipientType($request);
    $event = sanitize_key((string) $request->get_param('event'));

    if (! $recipientType || ! $this->templates->find($event, $recipientType)) {
      return $this->notFound();
    }

    try {
      $data = [];

      foreach (
        ['status', 'subject', 'html_content', 'plain_text_content'] as $field
      ) {
        if ($request->has_param($field)) {
          $data[$field] = wp_unslash(
            (string) $request->get_param($field)
          );
        }
      }

      $template = $this->templates->update($event, $recipientType, $data);

      return RestResponse::success(
        $this->data($template),
        'Notification template saved.',
      );
    } catch (InvalidArgumentException $exception) {
      return RestResponse::error(
        $exception->getMessage(),
        'INVALID_NOTIFICATION_TEMPLATE',
        [],
        422,
      );
    }
  }

  public function reset(WP_REST_Request $request): WP_REST_Response {
    $recipientType = $this->recipientType($request);
    $event = sanitize_key((string) $request->get_param('event'));

    if (! $recipientType || ! $this->templates->find($event, $recipientType)) {
      return $this->notFound();
    }

    $this->templates->reset($event, $recipientType);
    $template = $this->templates->find($event, $recipientType);

    return $template
      ? RestResponse::success(
        $this->data($template),
        'Notification template reset to its default.',
      )
      : $this->notFound();
  }

  public function preview(WP_REST_Request $request): WP_REST_Response {
    $recipientType = $this->recipientType($request);
    $event = sanitize_key((string) $request->get_param('event'));

    if (! $recipientType || ! $this->templates->find($event, $recipientType)) {
      return $this->notFound();
    }

    try {
      $preview = $this->templates->preview(
        $event,
        $recipientType,
        $this->draft($request),
      );

      return RestResponse::success(
        [
          'subject' => $preview->subject,
          'html_content' => $preview->htmlContent,
          'plain_text_content' => $preview->plainTextContent,
        ],
        'Notification template preview rendered.',
        ['sample_context' => $this->templates->sampleContext()],
      );
    } catch (InvalidArgumentException $exception) {
      return $this->invalid($exception);
    }
  }

  public function testEmail(WP_REST_Request $request): WP_REST_Response {
    $recipient = sanitize_email(
      wp_unslash((string) $request->get_param('test_recipient'))
    );

    if (! is_email($recipient)) {
      return RestResponse::error(
        'A valid test email recipient is required.',
        'INVALID_TEST_EMAIL_RECIPIENT',
        [],
        422,
      );
    }

    $recipientType = $this->recipientType($request);
    $event = sanitize_key((string) $request->get_param('event'));

    if (! $recipientType || ! $this->templates->find($event, $recipientType)) {
      return $this->notFound();
    }

    try {
      $preview = $this->templates->preview(
        $event,
        $recipientType,
        $this->draft($request),
      );
      $sent = $this->notifications->send(new NotificationData(
        event: 'template_test',
        recipient: $recipient,
        subject: $preview->subject,
        content: $preview->htmlContent,
        headers: ['Content-Type: text/html; charset=UTF-8'],
        metadata: [
          'template_event' => $event,
          'template_recipient_type' => $recipientType->value,
        ],
      ));

      return $sent
        ? RestResponse::success(
          ['recipient' => $recipient, 'sent' => true],
          'Test email sent through WordPress.',
        )
        : RestResponse::error(
          'WordPress could not send the test email.',
          'TEST_EMAIL_DELIVERY_FAILED',
          [],
          502,
        );
    } catch (InvalidArgumentException $exception) {
      return $this->invalid($exception);
    }
  }

  private function template(WP_REST_Request $request): ?NotificationTemplate {
    $recipientType = $this->recipientType($request);

    return $recipientType
      ? $this->templates->find(
        sanitize_key((string) $request->get_param('event')),
        $recipientType,
      )
      : null;
  }

  private function recipientType(
    WP_REST_Request $request,
  ): ?NotificationRecipientType {
    return NotificationRecipientType::tryFrom(
      sanitize_key((string) $request->get_param('recipient'))
    );
  }

  private function notFound(): WP_REST_Response {
    return RestResponse::error(
      'Notification template was not found.',
      'NOTIFICATION_TEMPLATE_NOT_FOUND',
      [],
      404,
    );
  }

  private function invalid(
    InvalidArgumentException $exception,
  ): WP_REST_Response {
    return RestResponse::error(
      $exception->getMessage(),
      'INVALID_NOTIFICATION_TEMPLATE',
      [],
      422,
    );
  }

  /** @return array<string, string> */
  private function draft(WP_REST_Request $request): array {
    $data = [];

    foreach (
      ['status', 'subject', 'html_content', 'plain_text_content'] as $field
    ) {
      if ($request->has_param($field)) {
        $data[$field] = wp_unslash((string) $request->get_param($field));
      }
    }

    return $data;
  }

  /** @return array<string, mixed> */
  private function data(NotificationTemplate $template): array {
    return [
      'key' => $template->key(),
      ...$template->toArray(),
    ];
  }

  /** @return string[] */
  private function placeholders(): array {
    return [
      'site_name', 'site_url', 'current_date',
      'customer_name', 'customer_email',
      'agent_name', 'agent_email',
      'ticket_id', 'track_id', 'ticket_subject', 'ticket_status',
      'ticket_priority', 'category_name', 'ticket_url',
      'reply_content', 'product_name', 'license_type', 'support_until',
    ];
  }
}
