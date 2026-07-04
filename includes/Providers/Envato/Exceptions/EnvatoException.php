<?php

declare(strict_types=1);

namespace SupportBay\Providers\Envato\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Base exception for all Envato integration errors.
 */
class EnvatoException extends RuntimeException {
  /**
   * Constructor.
   */
  public function __construct(
    string $message = 'Envato integration error.',
    int $code = 0,
    ?Throwable $previous = null,
  ) {
    parent::__construct(
      $message,
      $code,
      $previous,
    );
  }
}
