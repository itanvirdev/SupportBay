<?php

declare(strict_types=1);

namespace SupportBay\Modules\Notifications\Services;

use InvalidArgumentException;
use SupportBay\Modules\Notifications\Data\RenderedNotificationTemplate;
use SupportBay\Modules\Notifications\Entities\NotificationTemplate;
use SupportBay\Modules\Notifications\Enums\NotificationRecipientType;
use SupportBay\Modules\Notifications\Enums\NotificationTemplateStatus;
use SupportBay\Modules\Notifications\Repositories\NotificationTemplateRepository;

final class NotificationTemplateService {
  public function __construct(
    private readonly NotificationTemplateRepository $templates,
  ) {
  }

  /** @return NotificationTemplate[] */
  public function all(): array {
    return $this->templates->all();
  }

  public function find(
    string $event,
    NotificationRecipientType $recipientType,
  ): ?NotificationTemplate {
    return $this->templates->find($event, $recipientType);
  }

  /** @param array<string, mixed> $data */
  public function update(
    string $event,
    NotificationRecipientType $recipientType,
    array $data,
  ): NotificationTemplate {
    $existing = $this->find($event, $recipientType);

    if (! $existing) {
      throw new InvalidArgumentException('Notification template not found.');
    }

    $template = $this->build($existing, $data);
    $this->templates->save($template);

    return $template;
  }

  /**
   * Render sanitized draft content without persisting it.
   *
   * @param array<string, mixed> $data
   * @param array<string, scalar|null>|null $context
   */
  public function preview(
    string $event,
    NotificationRecipientType $recipientType,
    array $data = [],
    ?array $context = null,
  ): RenderedNotificationTemplate {
    $existing = $this->find($event, $recipientType);

    if (! $existing) {
      throw new InvalidArgumentException('Notification template not found.');
    }

    return $this->renderTemplate(
      $this->build($existing, $data),
      $context ?? $this->sampleContext(),
    );
  }

  /** @return array<string, scalar|null> */
  public function sampleContext(): array {
    return [
      'site_name' => get_bloginfo('name') ?: 'SupportBay Demo',
      'site_url' => home_url('/'),
      'current_date' => wp_date((string) get_option('date_format')),
      'customer_name' => 'Alex Customer',
      'customer_email' => 'alex@example.com',
      'agent_name' => 'Sam Agent',
      'agent_email' => 'agent@example.com',
      'ticket_id' => 42,
      'track_id' => '54E5DF43',
      'ticket_subject' => 'Demo support request',
      'ticket_status' => 'Open',
      'ticket_priority' => 'Normal',
      'category_name' => 'General',
      'ticket_url' => home_url('/support/tickets/42/'),
      'reply_content' => 'This is a sample ticket reply.',
      'product_name' => 'SupportBay Demo Product',
      'license_type' => 'Regular License',
      'support_until' => 'December 31, 2026',
    ];
  }

  /** @param array<string, mixed> $data */
  private function build(
    NotificationTemplate $existing,
    array $data,
  ): NotificationTemplate {
    $status = NotificationTemplateStatus::tryFrom(
      sanitize_key((string) ($data['status'] ?? $existing->status()->value))
    );

    if (! $status) {
      throw new InvalidArgumentException('Invalid template status.');
    }

    $subject = sanitize_text_field(
      (string) ($data['subject'] ?? $existing->subject())
    );
    $htmlContent = wp_kses_post(
      (string) ($data['html_content'] ?? $existing->htmlContent())
    );
    $plainTextContent = sanitize_textarea_field(
      (string) ($data['plain_text_content'] ?? $existing->plainTextContent())
    );

    if ($subject === '' || ($htmlContent === '' && $plainTextContent === '')) {
      throw new InvalidArgumentException(
        'Template subject and content are required.'
      );
    }

    return new NotificationTemplate(
      name: $existing->name(),
      event: $existing->event(),
      recipientType: $existing->recipientType(),
      status: $status,
      subject: $subject,
      htmlContent: $htmlContent,
      plainTextContent: $plainTextContent,
    );
  }

  public function reset(
    string $event,
    NotificationRecipientType $recipientType,
  ): void {
    $this->templates->reset($event, $recipientType);
  }

  /** @param array<string, scalar|null> $context */
  public function render(
    string $event,
    NotificationRecipientType $recipientType,
    array $context,
  ): ?RenderedNotificationTemplate {
    $template = $this->find($event, $recipientType);

    if (! $template || ! $template->isActive()) {
      return null;
    }

    return $this->renderTemplate($template, $context);
  }

  /** @param array<string, scalar|null> $context */
  private function renderTemplate(
    NotificationTemplate $template,
    array $context,
  ): RenderedNotificationTemplate {
    return new RenderedNotificationTemplate(
      subject: $this->replace($template->subject(), $context, 'subject'),
      htmlContent: $this->replace($template->htmlContent(), $context, 'html'),
      plainTextContent: $this->replace(
        $template->plainTextContent(),
        $context,
        'plain',
      ),
    );
  }

  /** @param array<string, scalar|null> $context */
  private function replace(
    string $content,
    array $context,
    string $format,
  ): string {
    foreach ($context as $key => $value) {
      $key = sanitize_key($key);
      $replacement = match ($format) {
        'html' => wp_kses_post((string) $value),
        'plain' => sanitize_textarea_field((string) $value),
        default => sanitize_text_field((string) $value),
      };
      $content = str_replace(
        ['{{' . $key . '}}', '{' . $key . '}'],
        $replacement,
        $content,
      );
    }

    return $content;
  }
}
