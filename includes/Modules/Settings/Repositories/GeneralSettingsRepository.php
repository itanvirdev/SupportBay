<?php

declare(strict_types=1);

namespace SupportBay\Modules\Settings\Repositories;

final class GeneralSettingsRepository {
  private const OPTION = 'sbay_settings';

  /** @return array<string, mixed> */
  public function all(): array {
    $settings = get_option(self::OPTION, []);
    return is_array($settings) ? $settings : [];
  }

  /** @param array<string, mixed> $settings */
  public function save(array $settings): void {
    update_option(self::OPTION, $settings, false);
  }
}
