<?php
declare(strict_types=1);
namespace SupportBay\Modules\Settings\Repositories;
final class AutoCloseSettingsRepository {
  private const OPTION='sbay_auto_close_settings';
  /** @return array<string,mixed> */ public function all():array{$value=get_option(self::OPTION,[]);return is_array($value)?$value:[];}
  /** @param array<string,mixed> $settings */ public function save(array $settings):void{update_option(self::OPTION,$settings,false);}
}
