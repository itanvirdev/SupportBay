<?php

declare(strict_types=1);

namespace SupportBay\Core\Testing;

use RuntimeException;

final class Assert {
  /**
   * Assert that a value is true.
   */
  public static function true(bool $condition, string $message): void {
    if (! $condition) {
      throw new RuntimeException("❌ {$message}");
    }

    echo "✅ {$message}\n";
  }

  /**
   * Assert equality.
   */
  public static function equals(
    mixed $expected,
    mixed $actual,
    string $message
  ): void {
    if ($expected !== $actual) {
      throw new RuntimeException(
        "❌ {$message}\nExpected: " .
          var_export($expected, true) .
          "\nActual: " .
          var_export($actual, true)
      );
    }

    echo "✅ {$message}\n";
  }

  /**
   * Assert value is not null.
   */
  public static function notNull(
    mixed $value,
    string $message
  ): void {
    self::true($value !== null, $message);
  }

  /**
   * Assert count.
   */
  public static function count(
    int $expected,
    array $items,
    string $message
  ): void {
    self::equals(
      $expected,
      count($items),
      $message
    );
  }
}
