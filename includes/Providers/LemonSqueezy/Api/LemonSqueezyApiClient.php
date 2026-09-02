<?php

declare(strict_types=1);

namespace SupportBay\Providers\LemonSqueezy\Api;

use RuntimeException;

final class LemonSqueezyApiClient {
  private const API_BASE = 'https://api.lemonsqueezy.com/v1';

  /** @return array<string, mixed> */
  public function validateLicense(string $licenseKey): array {
    $response = wp_remote_post(self::API_BASE . '/licenses/validate', [
      'timeout' => 30,
      'headers' => ['Accept' => 'application/json'],
      'body' => ['license_key' => $licenseKey],
    ]);
    if (is_wp_error($response)) {
      throw new RuntimeException(sanitize_text_field($response->get_error_message()));
    }

    $status = wp_remote_retrieve_response_code($response);
    $payload = json_decode((string) wp_remote_retrieve_body($response), true);
    if (! is_array($payload)) {
      throw new RuntimeException('Lemon Squeezy returned an invalid license response.');
    }
    if ($status >= 400 || empty($payload['valid'])) {
      $message = sanitize_text_field((string) ($payload['error'] ?? 'License key is invalid.'));
      throw new RuntimeException($message !== '' ? $message : 'License key is invalid.');
    }

    return $payload;
  }
}
