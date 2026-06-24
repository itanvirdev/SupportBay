<?php

declare(strict_types=1);

namespace SupportBay\Core\Events\Contracts;

interface Event {
  /**
   * Event name.
   */
  public function name(): string;
}
