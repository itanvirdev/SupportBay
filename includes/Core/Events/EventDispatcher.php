<?php

declare(strict_types=1);

namespace SupportBay\Core\Events;

use SupportBay\Core\Events\Contracts\Event;
use SupportBay\Core\Events\Contracts\Listener;

final class EventDispatcher {
  /**
   * Registered listeners.
   *
   * @var array<string, Listener[]>
   */
  private array $listeners = [];

  /**
   * Register a listener.
   */
  public function listen(string $event, Listener $listener): void {
    $this->listeners[$event][] = $listener;
  }

  /**
   * Dispatch an event.
   */
  public function dispatch(Event $event): void {
    foreach ($this->getListeners($event->name()) as $listener) {
      $listener->handle($event);
    }
  }

  /**
   * Determine whether an event has listeners.
   */
  public function hasListeners(string $event): bool {
    return !empty($this->listeners[$event]);
  }

  /**
   * Get listeners for an event.
   *
   * @return Listener[]
   */
  public function getListeners(string $event): array {
    return $this->listeners[$event] ?? [];
  }

  /**
   * Remove listeners for an event.
   */
  public function forget(string $event): void {
    unset($this->listeners[$event]);
  }

  /**
   * Remove all listeners.
   */
  public function flush(): void {
    $this->listeners = [];
  }
}
