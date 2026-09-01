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
    bool $authorizationOnly = false,
  ): array {
    if (! empty($query)) {
      $endpoint .= '?' . http_build_query($query);
    }

    return $this->request(
      'GET',
      $endpoint,
      $accessToken,
      [],
      false,
      $authorizationOnly,
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
   * Perform an OAuth token POST request without bearer authentication.
   *
   * WordPress encodes the array as application/x-www-form-urlencoded.
   *
   * @return array<string, mixed>
   */
  public function postForm(
    string $endpoint,
    array $body,
  ): array {
    return $this->request(
      'POST',
      $endpoint,
      '',
      $body,
      true,
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
    bool $formEncoded = false,
    bool $authorizationOnly = false,
  ): array {
    // Always include Accept header - Envato API requires it
    $headers = [
      'Accept' => 'application/json',
    ];

    // Add User-Agent only for non-authorization-only requests
    if (! $authorizationOnly) {
      $headers['User-Agent'] = sprintf(
        'SupportBay/%s; %s',
        defined('SBAY_VERSION') ? SBAY_VERSION : 'development',
        home_url('/'),
      );
    }

    if ($method !== 'GET') {
      $headers['Content-Type'] = $formEncoded
        ? 'application/x-www-form-urlencoded'
        : 'application/json';
    }
    if (trim($accessToken) !== '') {
      $headers['Authorization'] = 'Bearer ' . $accessToken;
    }

    $response = wp_remote_request(
      self::API_BASE . $endpoint,
      [
        'method'  => $method,
        'timeout' => $authorizationOnly ? 120 : 30,
        'headers' => $headers,
        'body' => empty($body)
          ? null
          : ($formEncoded ? $body : wp_json_encode($body)),
      ]
    );

    if (is_wp_error($response)) {
      throw new EnvatoException(
        $response->get_error_message()
      );
    }

    $status = wp_remote_retrieve_response_code($response);

    $raw = preg_replace('/^\xEF\xBB\xBF/', '', wp_remote_retrieve_body($response)) ?? '';
    $json = json_decode($raw, true);
    if (! is_array($json)) {
      $form = [];
      parse_str($raw, $form);
      $json = is_array($form) && (
        isset($form['access_token'])
        || isset($form['error'])
        || isset($form['error_description'])
      )
        ? $form
        : null;
    }

    if ($status >= 400) {
      $message = is_array($json)
        ? ($json['error_description'] ?? $json['error'] ?? $json['message'] ?? null)
        : null;
      $message = is_scalar($message) ? sanitize_text_field((string) $message) : '';
      if ($message === '') {
        $message = $this->responseExcerpt($raw);
      }
      if ($message === '') {
        $message = sprintf('Envato API request failed (HTTP %d).', $status);
      }

      $this->debugResponse($status, $response, $message);

      throw new EnvatoException(
        $message,
        $status,
      );
    }

    // Check for error in response (Envato returns 200 with error message)
    if (is_array($json) && isset($json['error'])) {
      $errorMessage = is_scalar($json['error'])
        ? sanitize_text_field((string) $json['error'])
        : $this->responseExcerpt($raw);
      $this->debugResponse($status, $response, $errorMessage);

      throw new EnvatoException($errorMessage, $status);
    }

    if (! is_array($json)) {
      $message = $this->responseExcerpt($raw);
      $this->debugResponse($status, $response, $message);
      throw new EnvatoException(sprintf(
        'Envato returned an unexpected response (HTTP %d)%s.',
        $status,
        $message !== '' ? ': ' . $message : '',
      ));
    }

    return $json;
  }

  private function responseExcerpt(string $raw): string {
    $text = sanitize_text_field(wp_strip_all_tags($raw));
    $text = preg_replace('/\s+/', ' ', $text) ?? '';
    return wp_html_excerpt(trim($text), 240, '');
  }

  /** @param array<string, mixed> $response */
  private function debugResponse(int $status, array $response, string $message): void {
    if (! defined('WP_DEBUG') || ! WP_DEBUG) {
      return;
    }
    error_log(sprintf(
      '[SupportBay Envato] HTTP %d; content-type=%s; response=%s',
      $status,
      sanitize_text_field((string) wp_remote_retrieve_header($response, 'content-type')),
      $message !== '' ? $message : '(empty response)',
    ));
  }
}
