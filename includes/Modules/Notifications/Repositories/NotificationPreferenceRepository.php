<?php

declare(strict_types=1);

namespace SupportBay\Modules\Notifications\Repositories;

use SupportBay\Modules\Notifications\Entities\NotificationPreference;
use SupportBay\Modules\Notifications\Templates\DefaultNotificationTemplates;

final class NotificationPreferenceRepository {
  private const OPTION = 'sbay_notification_preferences';

  public function __construct(
    private readonly DefaultNotificationTemplates $defaults,
  ) {
  }

  public function get(): NotificationPreference {
    $stored = get_option(self::OPTION, []);
    $stored = is_array($stored) ? $stored : [];
    $events = $this->defaultEvents();
    $savedEvents = isset($stored['events']) && is_array($stored['events'])
      ? $stored['events']
      : [];

    foreach ($events as $event => $recipients) {
      foreach ($recipients as $recipient => $default) {
        if (isset($savedEvents[$event]) && is_array($savedEvents[$event])) {
          $events[$event][$recipient] = array_key_exists(
            $recipient,
            $savedEvents[$event],
          ) ? (bool) $savedEvents[$event][$recipient] : $default;
        }
      }
    }

    return new NotificationPreference(
      enabled: array_key_exists('enabled', $stored)
        ? (bool) $stored['enabled']
        : true,
      events: $events,
    );
  }

  public function save(NotificationPreference $preference): void {
    update_option(self::OPTION, $preference->toArray(), false);
  }

  /** @return array<string, array<string, bool>> */
  private function defaultEvents(): array {
    $events = [];

    foreach ($this->defaults->all() as $template) {
      $event = sanitize_key($template['event']);
      $recipient = sanitize_key($template['recipient_type']);
      $events[$event][$recipient] = true;
    }

    return $events;
  }
}
