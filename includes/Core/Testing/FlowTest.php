<?php

declare(strict_types=1);

namespace SupportBay\Core\Testing;

use Throwable;

abstract class FlowTest {
  /**
   * Entry point.
   */
  final public static function run(...$services): void {
    echo '<pre>';

    try {
      static::execute(...$services);

      echo PHP_EOL;
      echo "🎯 " . static::title() . " Passed." . PHP_EOL;
    } catch (Throwable $e) {

      echo PHP_EOL;
      echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" . PHP_EOL;
      echo "❌ TEST FAILED" . PHP_EOL;
      echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" . PHP_EOL;
      echo $e->getMessage() . PHP_EOL;
      echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" . PHP_EOL;
    }

    echo '</pre>';
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
