<?php

declare(strict_types=1);

namespace SupportBay\Modules\Notifications\Entities;

use SupportBay\Core\Entities\Entity;
use SupportBay\Modules\Notifications\Enums\NotificationRecipientType;

final class NotificationPreference extends Entity {
  /** @param array<string, array<string, bool>> $events */
  public function __construct(
    private bool $enabled,
    private array $events,
  ) {
  }

  public function toArray(): array {
    return [
      'enabled' => $this->enabled,
      'events' => $this->events,
    ];
  }

  public function enabled(): bool { return $this->enabled; }

  /** @return array<string, array<string, bool>> */
  public function events(): array { return $this->events; }

  public function allows(
    string $event,
    NotificationRecipientType $recipientType,
  ): bool {
    return $this->enabled
      && ($this->events[sanitize_key($event)][$recipientType->value] ?? false);
  }
}
