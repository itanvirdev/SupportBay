<?php

declare(strict_types=1);

namespace SupportBay\Modules\Providers\Services;

use RuntimeException;

final class ProviderConfiguration {
  /**
   * Constructor.
   */
  public function __construct(
    private readonly ProviderService $providers,
  ) {
  }

  /**
   * Retrieve all provider settings.
   *
   * @return array<string, mixed>
   */
  public function all(string $slug): array {
    $provider = $this->providers->findBySlug($slug);

    if (! $provider) {
      throw new RuntimeException(
        sprintf(
          'Provider "%s" was not found.',
          $slug
        )
      );
    }

    $settings = $provider->settings();

    if (empty($settings)) {
      return [];
    }

    $decoded = json_decode($settings, true);

    return is_array($decoded)
      ? $decoded
      : [];
  }

  /**
   * Retrieve a configuration value.
   */
  public function get(
    string $slug,
    string $key,
    mixed $default = null,
  ): mixed {

    $settings = $this->all($slug);

    return $settings[$key] ?? $default;
  }

  /**
   * Determine whether a configuration value exists.
   */
  public function has(
    string $slug,
    string $key,
  ): bool {

    $settings = $this->all($slug);

    return array_key_exists(
      $key,
      $settings
    );
  }

  /**
   * Update a configuration value.
   */
  public function set(
    string $slug,
    string $key,
    mixed $value,
  ): void {

    $provider = $this->providers->findBySlug($slug);

    if (! $provider) {
      throw new RuntimeException(
        sprintf(
          'Provider "%s" was not found.',
          $slug
        )
      );
    }

    $settings = $this->all($slug);

    $settings[$key] = $value;

    $this->providers->update(
      $provider->id(),
      [
        'settings' => wp_json_encode($settings),
      ]
    );
  }

  /**
   * Retrieve the provider Client ID.
   */
  public function clientId(string $slug): ?string {
    return $this->get(
      $slug,
      'client_id'
    );
  }

  /**
   * Retrieve the provider Client Secret.
   */
  public function clientSecret(string $slug): ?string {
    return $this->get(
      $slug,
      'client_secret'
    );
  }

  /**
   * Retrieve the provider Redirect URI.
   */
  public function redirectUri(string $slug): ?string {
    return $this->get(
      $slug,
      'redirect_uri'
    );
  }

  /**
   * Determine whether the provider is configured.
   */
  public function configured(string $slug): bool {
    return
      ! empty($this->clientId($slug))
      && ! empty($this->clientSecret($slug));
  }
}
