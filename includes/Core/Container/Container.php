<?php

declare(strict_types=1);

namespace SupportBay\Core\Container;

final class Container {
  /**
   * Service bindings.
   *
   * @var array<string, callable>
   */
  private array $bindings = [];

  /**
   * Shared instances.
   *
   * @var array<string, object>
   */
  private array $instances = [];

  /**
   * Shared flags.
   *
   * @var array<string, bool>
   */
  private array $shared = [];

  /**
   * Register a factory binding.
   */
  public function bind(string $abstract, callable $resolver): void {
    $this->bindings[$abstract] = $resolver;
    $this->shared[$abstract] = false;
  }

  /**
   * Register a singleton binding.
   */
  public function singleton(string $abstract, callable $resolver): void {
    $this->bindings[$abstract] = $resolver;
    $this->shared[$abstract] = true;
  }

  /**
   * Register an existing instance.
   */
  public function instance(string $abstract, object $instance): void {
    $this->instances[$abstract] = $instance;
    $this->shared[$abstract] = true;
  }

  /**
   * Resolve a service.
   */
  public function make(string $abstract): mixed {
    // Existing singleton instance
    if (isset($this->instances[$abstract])) {
      return $this->instances[$abstract];
    }

    if (! isset($this->bindings[$abstract])) {
      return null;
    }

    $object = ($this->bindings[$abstract])($this);

    if ($this->shared[$abstract]) {
      $this->instances[$abstract] = $object;
    }

    return $object;
  }

  /**
   * Alias of make().
   */
  public function get(string $abstract): mixed {
    return $this->make($abstract);
  }

  /**
   * Check whether a service exists.
   */
  public function has(string $abstract): bool {
    return isset($this->bindings[$abstract])
      || isset($this->instances[$abstract]);
  }

  /**
   * Forget a binding.
   */
  public function forget(string $abstract): void {
    unset(
      $this->bindings[$abstract],
      $this->instances[$abstract],
      $this->shared[$abstract]
    );
  }

  /**
   * Debug helper.
   */
  public function all(): array {
    return [
      'bindings' => array_keys($this->bindings),
      'instances' => array_keys($this->instances),
      'shared' => $this->shared,
    ];
  }
}
