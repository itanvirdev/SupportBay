<?php

declare(strict_types=1);

namespace SupportBay\Modules\Notifications\Entities;

use SupportBay\Core\Entities\Entity;
use SupportBay\Modules\Notifications\Enums\NotificationRecipientType;
use SupportBay\Modules\Notifications\Enums\NotificationTemplateStatus;

final class NotificationTemplate extends Entity {
  public function __construct(
    private string $name,
    private string $event,
    private NotificationRecipientType $recipientType,
    private NotificationTemplateStatus $status,
    private string $subject,
    private string $htmlContent,
    private string $plainTextContent,
  ) {
  }

  public function toArray(): array {
    return [
      'name' => $this->name,
      'event' => $this->event,
      'recipient_type' => $this->recipientType->value,
      'status' => $this->status->value,
      'subject' => $this->subject,
      'html_content' => $this->htmlContent,
      'plain_text_content' => $this->plainTextContent,
    ];
  }

  public function name(): string { return $this->name; }
  public function event(): string { return $this->event; }
  public function recipientType(): NotificationRecipientType { return $this->recipientType; }
  public function status(): NotificationTemplateStatus { return $this->status; }
  public function subject(): string { return $this->subject; }
  public function htmlContent(): string { return $this->htmlContent; }
  public function plainTextContent(): string { return $this->plainTextContent; }

  public function key(): string {
    return $this->event . ':' . $this->recipientType->value;
  }

  public function isActive(): bool {
    return $this->status->isActive();
  }
}
