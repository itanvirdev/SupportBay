<?php

declare(strict_types=1);

namespace SupportBay\Core\Entities;

use JsonSerializable;

abstract class Entity implements JsonSerializable {
  /**
   * Convert entity to array.
   */
  abstract public function toArray(): array;

  /**
   * JSON serialization.
   */
  public function jsonSerialize(): array {
    return $this->toArray();
  }

  /**
   * Magic getter for read-only properties.
   */
  public function __get(string $name): mixed {
    return property_exists($this, $name)
      ? $this->{$name}
      : null;
  }

  /**
   * Check if a property exists.
   */
  public function has(string $property): bool {
    return property_exists($this, $property);
  }

  /**
   * Convert entity to JSON.
   */
  public function toJson(int $flags = 0): string {
    return json_encode($this->toArray(), $flags | JSON_THROW_ON_ERROR);
  }
}
