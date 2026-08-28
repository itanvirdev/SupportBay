<?php

declare(strict_types=1);

namespace SupportBay\Core\Testing;

use Throwable;

abstract class FlowTest {
  private static int $failureCount = 0;

  /**
   * Entry point.
   */
  final public static function run(...$services): bool {
    echo '<pre>';

    $passed = true;

    try {
      static::execute(...$services);

      echo PHP_EOL;
      echo "🎯 " . static::title() . " Passed." . PHP_EOL;
    } catch (Throwable $e) {
      $passed = false;
      self::$failureCount++;

      echo PHP_EOL;
      echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" . PHP_EOL;
      echo "❌ TEST FAILED" . PHP_EOL;
      echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" . PHP_EOL;
      echo $e->getMessage() . PHP_EOL;
      echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" . PHP_EOL;
    }

    echo '</pre>';

    return $passed;
  }

  public static function failureCount(): int {
    return self::$failureCount;
  }

  /**
   * Test title.
   */
  abstract protected static function title(): string;

  /**
   * Execute flow.
   */
  abstract protected static function execute(...$services): void;
}
