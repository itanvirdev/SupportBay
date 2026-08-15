<?php

declare(strict_types=1);

namespace SupportBay\Modules\Notifications\Services;

use InvalidArgumentException;
use SupportBay\Modules\Notifications\Entities\NotificationPreference;
use SupportBay\Modules\Notifications\Enums\NotificationRecipientType;
use SupportBay\Modules\Notifications\Repositories\NotificationPreferenceRepository;

final class NotificationPreferenceService {
  public function __construct(
    private readonly NotificationPreferenceRepository $preferences,
  ) {
  }

  public function get(): NotificationPreference {
    return $this->preferences->get();
  }

  /** @param array<string, mixed> $data */
  public function update(array $data): NotificationPreference {
    $existing = $this->get();
    $enabled = array_key_exists('enabled', $data)
      ? $this->boolean($data['enabled'])
      : $existing->enabled();
    $events = $existing->events();

    if (array_key_exists('events', $data)) {
      if (! is_array($data['events'])) {
        throw new InvalidArgumentException('Notification events must be an object.');
      }

      foreach ($data['events'] as $event => $recipients) {
        $event = sanitize_key((string) $event);

        if (! isset($events[$event]) || ! is_array($recipients)) {
          throw new InvalidArgumentException('Unknown notification event.');
        }

        foreach ($recipients as $recipient => $allowed) {
          $recipient = sanitize_key((string) $recipient);

          if (! array_key_exists($recipient, $events[$event])) {
            throw new InvalidArgumentException('Unknown notification recipient.');
          }

          $events[$event][$recipient] = $this->boolean($allowed);
        }
      }
    }

    $preference = new NotificationPreference(
      enabled: $enabled,
      events: $events,
    );
    $this->preferences->save($preference);

    return $preference;
  }

  public function allows(
    string $event,
    NotificationRecipientType $recipientType,
  ): bool {
    return $this->get()->allows($event, $recipientType);
  }

  private function boolean(mixed $value): bool {
    if (is_bool($value)) {
      return $value;
    }

    if (in_array($value, [0, 1, '0', '1'], true)) {
      return (bool) $value;
    }

    throw new InvalidArgumentException('Notification preference must be boolean.');
  }
}
