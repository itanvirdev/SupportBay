<?php

declare(strict_types=1);

namespace SupportBay\Core\Events;

final class ListenerRegistry {
    /**
     * Registered event listeners.
     *
     * @var array<string, array<int, class-string>>
     */
    private static array $listeners = [];

    /**
     * Register a listener for an event.
     */
    public static function add(
        string $eventClass,
        string $listenerClass
    ): void {
        self::$listeners[$eventClass] ??= [];

        if (!in_array($listenerClass, self::$listeners[$eventClass], true)) {
            self::$listeners[$eventClass][] = $listenerClass;
        }
    }

    /**
     * Get listeners for an event.
     *
     * @return array<int, class-string>
     */
    public static function listeners(string $eventClass): array {
        return self::$listeners[$eventClass] ?? [];
    }

    /**
     * Determine if an event has listeners.
     */
    public static function has(string $eventClass): bool {
        return !empty(self::$listeners[$eventClass]);
    }

    /**
     * Get all registered listeners.
     *
     * @return array<string, array<int, class-string>>
     */
    public static function all(): array {
        return self::$listeners;
    }

    /**
     * Clear all listeners.
     *
     * Useful for testing.
     */
    public static function flush(): void {
        self::$listeners = [];
    }
}
