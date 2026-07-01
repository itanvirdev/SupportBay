<?php

declare(strict_types=1);

namespace SupportBay\Modules\Providers\Enums;

enum ProviderStatus: string {
  /**
   * Provider is enabled.
   */
  case ENABLED = 'enabled';

  /**
   * Provider is disabled.
   */
  case DISABLED = 'disabled';

  /**
   * Human-readable label.
   */
  public function label(): string {
    return match ($this) {
      self::ENABLED  => 'Enabled',
      self::DISABLED => 'Disabled',
    };
  }

  /**
   * Whether the provider is enabled.
   */
  public function isEnabled(): bool {
    return $this === self::ENABLED;
  }

  /**
   * Whether the provider is disabled.
   */
  public function isDisabled(): bool {
    return $this === self::DISABLED;
  }

  /**
   * Create from database boolean.
   */
  public static function fromBoolean(bool|int $enabled): self {
    return (bool) $enabled
      ? self::ENABLED
      : self::DISABLED;
  }

  /**
   * Convert to database boolean.
   */
  public function toBoolean(): bool {
    return $this === self::ENABLED;
  }

  /**
   * Get all enum values.
   *
   * @return string[]
   */
  public static function values(): array {
    return array_map(
      static fn(self $status) => $status->value,
      self::cases()
    );
  }

  /**
   * Get key/value options.
   *
   * @return array<string, string>
   */
  public static function options(): array {
    $options = [];

    foreach (self::cases() as $status) {
      $options[$status->value] = $status->label();
    }

    return $options;
  }
}
