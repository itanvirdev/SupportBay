<?php

declare(strict_types=1);

namespace SupportBay\Core\Http;

use WP_REST_Response;

final class RestResponse {
  /**
   * Create a successful API response.
   *
   * @param array<string, mixed>|array<int, mixed> $data
   * @param array<string, mixed> $meta
   */
  public static function success(
    array $data,
    string $message = '',
    array $meta = [],
    int $status = 200,
  ): WP_REST_Response {
    return new WP_REST_Response([
      'success' => true,
      'message' => $message,
      'data'    => $data,
      'meta'    => $meta,
    ], $status);
  }

  /**
   * Create an API error response.
   *
   * @param array<string, string> $errors
   */
  public static function error(
    string $message,
    string $errorCode,
    array $errors = [],
    int $status = 400,
  ): WP_REST_Response {
    return new WP_REST_Response([
      'success'    => false,
      'message'    => $message,
      'error_code' => $errorCode,
      'errors'     => $errors,
    ], $status);
  }
}
