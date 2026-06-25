<?php

declare(strict_types=1);

namespace SupportBay\Core\Container;

use ReflectionClass;
use ReflectionNamedType;
use RuntimeException;

final class Container {
  /**
   * Service bindings.
   *
   * @var array<string, callable|string>
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
   * Currently resolving services.
   *
   * Used to detect circular dependencies.
   *
   * @var array<string, bool>
   */
  private array $resolving = [];

  /**
   * Register a factory binding.
   */
  public function bind(
    string $abstract,
    callable|string|null $resolver = null
  ): void {
    $this->bindings[$abstract] = $resolver ?? $abstract;
    $this->shared[$abstract] = false;
  }

  /**
   * Register a singleton binding.
   */
  public function singleton(
    string $abstract,
    callable|string|null $resolver = null
  ): void {
    $this->bindings[$abstract] = $resolver ?? $abstract;
    $this->shared[$abstract] = true;
  }

  /**
   * Register an existing instance.
   */
  public function instance(
    string $abstract,
    object $instance
  ): void {
    $this->instances[$abstract] = $instance;
    $this->shared[$abstract] = true;
  }

  /**
   * Resolve service.
   */
  public function make(string $abstract): mixed {
    if (isset($this->instances[$abstract])) {
      return $this->instances[$abstract];
    }

    if (isset($this->resolving[$abstract])) {
      throw new RuntimeException(
        sprintf(
          'Circular dependency detected while resolving [%s].',
          $abstract
        )
      );
    }

    $this->resolving[$abstract] = true;

    try {

      $object = $this->resolve($abstract);

      if (($this->shared[$abstract] ?? false) === true) {
        $this->instances[$abstract] = $object;
      }

      return $object;
    } finally {

      unset($this->resolving[$abstract]);
    }
  }

  /**
   * Alias of make().
   */
  public function get(string $abstract): mixed {
    return $this->make($abstract);
  }

  /**
   * Resolve binding or class.
   */
  private function resolve(string $abstract): mixed {
    if (isset($this->bindings[$abstract])) {

      $resolver = $this->bindings[$abstract];

      if (is_callable($resolver)) {
        return $resolver($this);
      }

      if (is_string($resolver)) {
        return $this->build($resolver);
      }
    }

    if (class_exists($abstract)) {
      return $this->build($abstract);
    }

    throw new RuntimeException(
      sprintf(
        'Unable to resolve [%s].',
        $abstract
      )
    );
  }

  /**
   * Build class using reflection.
   */
  private function build(string $class): object {
    $reflection = new ReflectionClass($class);

    if (!$reflection->isInstantiable()) {
      throw new RuntimeException(
        sprintf(
          'Class [%s] is not instantiable.',
          $class
        )
      );
    }

    $constructor = $reflection->getConstructor();

    if (!$constructor) {
      return new $class();
    }

    $dependencies = [];

    foreach ($constructor->getParameters() as $parameter) {

      $type = $parameter->getType();

      if (!$type instanceof ReflectionNamedType) {

        throw new RuntimeException(
          sprintf(
            'Unable to resolve parameter [$%s] in [%s].',
            $parameter->getName(),
            $class
          )
        );
      }

      if ($type->isBuiltin()) {

        if ($parameter->isDefaultValueAvailable()) {
          $dependencies[] = $parameter->getDefaultValue();
          continue;
        }

        throw new RuntimeException(
          sprintf(
            'Cannot autowire scalar parameter [$%s] in [%s].',
            $parameter->getName(),
            $class
          )
        );
      }

      $dependencies[] = $this->make(
        $type->getName()
      );
    }

    return $reflection->newInstanceArgs(
      $dependencies
    );
  }

  /**
   * Check whether a service exists.
   */
  public function has(string $abstract): bool {
    return isset($this->bindings[$abstract])
      || isset($this->instances[$abstract])
      || class_exists($abstract);
  }

  /**
   * Forget binding.
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
