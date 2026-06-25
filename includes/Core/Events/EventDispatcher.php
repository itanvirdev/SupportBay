<?php

declare(strict_types=1);

namespace SupportBay\Core\Events;

use RuntimeException;
use SupportBay\Core\Container\Container;
use SupportBay\Core\Events\Contracts\Event;
use SupportBay\Core\Events\Contracts\Listener;

final class EventDispatcher {
  /**
   * DI Container.
   */
  public function __construct(
    private readonly Container $container
  ) {
  }

  /**
   * Dispatch an event.
   */
  public function dispatch(Event $event): void {
    foreach (ListenerRegistry::listeners($event::class) as $listenerClass) {
      $listener = $this->resolve($listenerClass);

      $listener->handle($event);
    }
  }

  /**
   * Resolve listener from the container.
   */
  private function resolve(string $listenerClass): Listener {
    $listener = $this->container->get($listenerClass);

    if (!$listener instanceof Listener) {
      throw new RuntimeException(sprintf(
        'Listener [%s] is not registered in the container.',
        $listenerClass
      ));
    }

    return $listener;
  }
}
