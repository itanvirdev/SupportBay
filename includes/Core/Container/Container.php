<?php

declare(strict_types=1);

namespace SupportBay\Core\Container;

use RuntimeException;

final class Container {
  /**
   * All bindings
   */
  private array $bindings = [];

  /**
   * Resolved singleton instances
   */
  private array $instances = [];

  /**
   * Register a binding (factory-based)
   */
  public function bind(string $key, callable $resolver): void {
    $this->bindings[$key] = $resolver;
  }

  /**
   * Register a singleton binding
   */
  public function singleton(string $key, mixed $instance): void {
    $this->instances[$key] = $instance;
  }

  /**
   * Resolve a service from the container
   */
  public function get(string $key) {
    /**
     * Return singleton if exists
     */
    if (array_key_exists($key, $this->instances)) {
      return $this->instances[$key];
    }

    /**
     * Resolve from factory binding
     */
    if (isset($this->bindings[$key])) {
      return ($this->bindings[$key])($this);
    }

    return null;
  }

  /**
   * Check if service exists
   */
  public function has(string $key): bool {
    return isset($this->bindings[$key]) || array_key_exists($key, $this->instances);
  }

  /**
   * Remove a service (useful for testing or overrides)
   */
  public function forget(string $key): void {
    unset($this->bindings[$key], $this->instances[$key]);
  }

  /**
   * Get all registered services (debugging)
   */
  public function all(): array {
    return [
      'bindings' => $this->bindings,
      'instances' => $this->instances,
    ];
  }
}
