<?php

declare(strict_types=1);

namespace SupportBay\Modules\Notifications\Repositories;

use SupportBay\Modules\Notifications\Entities\NotificationTemplate;
use SupportBay\Modules\Notifications\Enums\NotificationRecipientType;
use SupportBay\Modules\Notifications\Enums\NotificationTemplateStatus;
use SupportBay\Modules\Notifications\Templates\DefaultNotificationTemplates;
use ValueError;

final class NotificationTemplateRepository {
  private const OPTION = 'sbay_notification_templates';

  public function __construct(
    private readonly DefaultNotificationTemplates $defaults,
  ) {
  }

  /** @return NotificationTemplate[] */
  public function all(): array {
    return array_values($this->resolved());
  }

  public function find(
    string $event,
    NotificationRecipientType $recipientType,
  ): ?NotificationTemplate {
    $key = sanitize_key($event) . ':' . $recipientType->value;
    return $this->resolved()[$key] ?? null;
  }

  public function save(NotificationTemplate $template): void {
    $stored = $this->stored();
    $stored[$template->key()] = $template->toArray();
    update_option(self::OPTION, $stored, false);
  }

  public function reset(
    string $event,
    NotificationRecipientType $recipientType,
  ): void {
    $stored = $this->stored();
    unset($stored[sanitize_key($event) . ':' . $recipientType->value]);
    update_option(self::OPTION, $stored, false);
  }

  /** @return array<string, NotificationTemplate> */
  private function resolved(): array {
    $resolved = [];
    $stored = $this->stored();

    foreach ($this->defaults->all() as $key => $default) {
      $template = isset($stored[$key]) && is_array($stored[$key])
        ? $this->hydrate($stored[$key])
        : null;
      $resolved[$key] = $template ?? $this->hydrate($default);
    }

    return array_filter($resolved);
  }

  /** @return array<string, array<string, mixed>> */
  private function stored(): array {
    $stored = get_option(self::OPTION, []);

    return is_array($stored) ? $stored : [];
  }

  /** @param array<string, mixed> $data */
  private function hydrate(array $data): ?NotificationTemplate {
    try {
      return new NotificationTemplate(
        name: (string) ($data['name'] ?? ''),
        event: sanitize_key((string) ($data['event'] ?? '')),
        recipientType: NotificationRecipientType::from(
          (string) ($data['recipient_type'] ?? '')
        ),
        status: NotificationTemplateStatus::from(
          (string) ($data['status'] ?? '')
        ),
        subject: (string) ($data['subject'] ?? ''),
        htmlContent: (string) ($data['html_content'] ?? ''),
        plainTextContent: (string) ($data['plain_text_content'] ?? ''),
      );
    } catch (ValueError) {
      return null;
    }
  }
}
