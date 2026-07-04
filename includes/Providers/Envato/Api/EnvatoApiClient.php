<?php

declare(strict_types=1);

namespace SupportBay\Providers\Envato\Api;

use SupportBay\Providers\Envato\Exceptions\EnvatoException;

final class EnvatoApiClient {
  /**
   * Envato API base URL.
   */
  private const API_BASE = 'https://api.envato.com';

  /**
   * Perform a GET request.
   *
   * @return array<string, mixed>
   *
   * @throws EnvatoException
   */
  public function get(
    string $endpoint,
    string $accessToken,
    array $query = [],
  ): array {
    if (! empty($query)) {
      $endpoint .= '?' . http_build_query($query);
    }

    return $this->request(
      'GET',
      $endpoint,
      $accessToken,
    );
  }

  /**
   * Perform a POST request.
   *
   * @return array<string, mixed>
   *
   * @throws EnvatoException
   */
  public function post(
    string $endpoint,
    string $accessToken,
    array $body = [],
  ): array {
    return $this->request(
      'POST',
      $endpoint,
      $accessToken,
      $body,
    );
  }

  /**
   * Perform an HTTP request.
   *
   * @return array<string, mixed>
   *
   * @throws EnvatoException
   */
  private function request(
    string $method,
    string $endpoint,
    string $accessToken,
    array $body = [],
  ): array {

    $response = wp_remote_request(
      self::API_BASE . $endpoint,
      [
        'method'  => $method,
        'timeout' => 30,
        'headers' => [
          'Authorization' => 'Bearer ' . $accessToken,
          'Accept'        => 'application/json',
          'Content-Type'  => 'application/json',
        ],
        'body' => empty($body)
          ? null
          : wp_json_encode($body),
      ]
    );

    if (is_wp_error($response)) {
      throw new EnvatoException(
        $response->get_error_message()
      );
    }

    $status = wp_remote_retrieve_response_code($response);

    $raw = wp_remote_retrieve_body($response);

    $json = json_decode($raw, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
      throw new EnvatoException(
        'Invalid JSON response received from Envato.'
      );
    }

    if ($status >= 400) {

      $message = $json['error_description']
        ?? $json['error']
        ?? $json['message']
        ?? sprintf(
          'Envato API request failed (%d).',
          $status
        );

      throw new EnvatoException(
        $message,
        $status,
      );
    }

    return is_array($json)
      ? $json
      : [];
  }
}
