<?php

declare(strict_types=1);

namespace SupportBay\Modules\Departments\Enums;

enum DepartmentStatus: string {
  case ACTIVE = 'active';
  case INACTIVE = 'inactive';

  /**
   * Default status
   */
  public static function default(): self {
    return self::ACTIVE;
  }

  /**
   * Active statuses
   *
   * @return self[]
   */
  public static function active(): array {
    return [
      self::ACTIVE,
    ];
  }

  /**
   * Available options
   */
  public static function options(): array {
    return [
      self::ACTIVE->value   => __('Active', 'supportbay'),
      self::INACTIVE->value => __('Inactive', 'supportbay'),
    ];
  }

  /**
   * Is active?
   */
  public function isActive(): bool {
    return $this === self::ACTIVE;
  }

  /**
   * Is inactive?
   */
  public function isInactive(): bool {
    return $this === self::INACTIVE;
  }

  /**
   * Human-readable label
   */
  public function label(): string {
    return match ($this) {
      self::ACTIVE   => __('Active', 'supportbay'),
      self::INACTIVE => __('Inactive', 'supportbay'),
    };
  }
}
