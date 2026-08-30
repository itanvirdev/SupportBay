<?php

declare(strict_types=1);

namespace SupportBay\Core\Events;

use RuntimeException;
use Throwable;
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

      try {
        $listener->handle($event);
      } catch (Throwable $exception) {
        do_action('sbay_listener_failed', $event, $listenerClass, $exception);

        if (defined('WP_DEBUG') && WP_DEBUG) {
          error_log(sprintf(
            '[SupportBay] Listener %s failed for %s: %s',
            $listenerClass,
            $event::class,
            $exception->getMessage(),
          ));
        }
      }
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
