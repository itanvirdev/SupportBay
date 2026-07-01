<?php

declare(strict_types=1);

namespace SupportBay\Modules\Providers\Enums;

enum ProviderCategory: string {
  /**
   * Marketplace integrations.
   */
  case MARKETPLACE = 'marketplace';

  /**
   * Artificial Intelligence providers.
   */
  case AI = 'ai';

  /**
   * Notification providers.
   */
  case NOTIFICATION = 'notification';

  /**
   * Payment providers.
   */
  case PAYMENT = 'payment';

  /**
   * Storage providers.
   */
  case STORAGE = 'storage';

  /**
   * Other integrations.
   */
  case OTHER = 'other';

  /**
   * Human-readable label.
   */
  public function label(): string {
    return match ($this) {
      self::MARKETPLACE => 'Marketplace',
      self::AI           => 'AI',
      self::NOTIFICATION => 'Notification',
      self::PAYMENT      => 'Payment',
      self::STORAGE      => 'Storage',
      self::OTHER        => 'Other',
    };
  }

  /**
   * Get all enum values.
   *
   * @return string[]
   */
  public static function values(): array {
    return array_map(
      static fn(self $category) => $category->value,
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

    foreach (self::cases() as $category) {
      $options[$category->value] = $category->label();
    }

    return $options;
  }
}
