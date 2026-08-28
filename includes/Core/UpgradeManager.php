<?php

declare(strict_types=1);

namespace SupportBay\Core;

final class UpgradeManager {
  public static function maybeUpgrade(): void {
    Activator::mergeDefaultOptions();
    $installed=(string)get_option('sbay_version','0.0.0');
    if (version_compare($installed,SBAY_VERSION,'>=')) { return; }

    foreach (self::migrations() as $version=>$migration) {
      if (version_compare($installed,$version,'<')) { $migration(); }
    }
    update_option('sbay_version',SBAY_VERSION,false);
  }

  /** @return array<string, callable():void> */
  private static function migrations(): array {
    // Ordered data migrations begin here after the consolidated v1 schema ships.
    return [];
  }
}
