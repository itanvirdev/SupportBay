<?php

declare(strict_types=1);

namespace SupportBay\Modules\Providers\Services;

use InvalidArgumentException;
use RuntimeException;
use SupportBay\Core\Integrations\Contracts\ConfigurableIntegrationProvider;
use SupportBay\Core\Integrations\Data\ProviderConfigurationField;
use SupportBay\Core\Integrations\IntegrationManager;
use SupportBay\Core\Security\SecretCipher;

final class ProviderConfiguration {
  public function __construct(
    private readonly ProviderService $providers,
    private readonly IntegrationManager $integrations,
    private readonly SecretCipher $cipher,
  ) {
  }

  /** @return array<string, mixed> */
  public function all(string $slug): array {
    $settings = $this->raw($slug);

    foreach ($settings as $key => $value) {
      if (is_string($value) && $this->cipher->isEncrypted($value)) {
        $settings[$key] = $this->cipher->decrypt($value);
      }
    }

    foreach ($this->fields($slug, false) as $field) {
      if (
        $field->defaultValue() !== null &&
        ($field->type() === 'readonly' || ! isset($settings[$field->key()]) || $settings[$field->key()] === '')
      ) {
        $settings[$field->key()] = $field->defaultValue();
      }
    }

    return $settings;
  }

  /** @return array<string, mixed> */
  public function form(string $slug): array {
    $values = $this->all($slug);

    return [
      'provider' => $slug,
      'configured' => $this->configured($slug),
      'fields' => array_map(
        static fn(ProviderConfigurationField $field): array => $field->toArray(
          $values[$field->key()] ?? '',
          ! empty($values[$field->key()]),
        ),
        $this->fields($slug),
      ),
    ];
  }

  /** @param array<string, mixed> $input */
  public function update(string $slug, array $input): void {
    $provider = $this->provider($slug);
    $raw = $this->raw($slug);
    $values = $this->all($slug);
    $allowed = [];

    foreach ($this->fields($slug) as $field) {
      $allowed[] = $field->key();

      if (! array_key_exists($field->key(), $input)) {
        continue;
      }

      $value = $this->sanitize($field, $input[$field->key()]);

      if ($field->isSecret() && $value === '') {
        $existing = $values[$field->key()] ?? '';

        if (is_string($existing) && $existing !== '' && ! $this->cipher->isEncrypted((string) ($raw[$field->key()] ?? ''))) {
          $raw[$field->key()] = $this->cipher->encrypt($existing);
        }

        continue;
      }

      $values[$field->key()] = $value;
      $raw[$field->key()] = $field->isSecret() && $value !== ''
        ? $this->cipher->encrypt($value)
        : $value;
    }

    $unknown = array_diff(array_keys($input), $allowed);

    if ($unknown !== []) {
      throw new InvalidArgumentException('Unsupported provider configuration field.');
    }

    foreach ($this->fields($slug) as $field) {
      if ($field->isRequired() && empty($values[$field->key()])) {
        throw new InvalidArgumentException(
          sprintf('%s is required.', $field->label())
        );
      }
    }

    $this->providers->updateSettings($provider->id(), $raw);
  }

  public function get(string $slug, string $key, mixed $default = null): mixed {
    return $this->all($slug)[$key] ?? $default;
  }

  public function has(string $slug, string $key): bool {
    return array_key_exists($key, $this->all($slug));
  }

  public function set(string $slug, string $key, mixed $value): void {
    $settings = $this->raw($slug);
    $settings[$key] = $value;
    $this->providers->updateSettings($this->provider($slug)->id(), $settings);
  }

  public function clientId(string $slug): ?string { return $this->string($slug, 'client_id'); }
  public function clientSecret(string $slug): ?string { return $this->string($slug, 'client_secret'); }
  public function redirectUri(string $slug): ?string { return $this->string($slug, 'redirect_uri'); }

  public function configured(string $slug): bool {
    $fields = $this->fields($slug, false);

    if ($fields === []) {
      return ! empty($this->raw($slug));
    }

    $values = $this->all($slug);

    foreach ($fields as $field) {
      if ($field->isRequired() && empty($values[$field->key()])) {
        return false;
      }
    }

    return true;
  }

  /** @return array<string, mixed> */
  private function raw(string $slug): array {
    return $this->provider($slug)->settings() ?? [];
  }

  private function provider(string $slug): \SupportBay\Modules\Providers\Entities\Provider {
    $provider = $this->providers->findBySlug($slug);

    if (! $provider) {
      throw new RuntimeException(sprintf('Provider "%s" was not found.', $slug));
    }

    return $provider;
  }

  /** @return ProviderConfigurationField[] */
  private function fields(string $slug, bool $required = true): array {
    if (! $this->integrations->has($slug)) {
      if ($required) {
        throw new RuntimeException('The provider integration is not registered.');
      }

      return [];
    }

    $integration = $this->integrations->integration($slug);

    if (! $integration instanceof ConfigurableIntegrationProvider) {
      if ($required) {
        throw new RuntimeException('This provider does not expose configurable settings.');
      }

      return [];
    }

    return $integration->configurationFields();
  }

  private function sanitize(ProviderConfigurationField $field, mixed $value): string {
    if ($field->type() === 'toggle') {
      return filter_var($value, FILTER_VALIDATE_BOOL) ? '1' : '0';
    }

    if ($field->type() === 'readonly') {
      return sanitize_text_field((string) $field->defaultValue());
    }

    $value = is_scalar($value) ? trim((string) $value) : '';

    return $field->type() === 'url'
      ? esc_url_raw($value)
      : sanitize_text_field($value);
  }

  private function string(string $slug, string $key): ?string {
    $value = $this->get($slug, $key);

    return is_string($value) && $value !== '' ? $value : null;
  }
}
