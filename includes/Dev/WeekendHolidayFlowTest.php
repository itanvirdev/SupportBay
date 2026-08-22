<?php

declare(strict_types=1);

namespace SupportBay\Dev;

use SupportBay\Core\Testing\Assert;
use SupportBay\Core\Testing\FlowTest;
use SupportBay\Modules\Settings\Services\WeekendHolidaySettingsService;

final class WeekendHolidayFlowTest extends FlowTest {
  protected static function title(): string {return 'Weekend & Holiday Flow Test';}
  protected static function execute(...$services): void {
    /** @var WeekendHolidaySettingsService $settings */
    [$settings]=$services;$previous=get_option('sbay_weekend_holiday_settings',[]);$now=current_datetime();
    try{
      $saved=$settings->update([
        'weekend_enabled'=>true,'weekend_days'=>[['day'=>(int)$now->format('w'),'all_day'=>true]],
        'weekend_portal_notice_enabled'=>true,'weekend_portal_notice'=>'Weekend test notice',
        'holiday_enabled'=>true,'holidays'=>[['name'=>'Test holiday','start_date'=>$now->format('Y-m-d'),'end_date'=>$now->format('Y-m-d')]],
        'holiday_portal_notice_enabled'=>true,'holiday_portal_notice'=>'Holiday test notice',
      ]);
      Assert::true($saved['timezone']===wp_timezone_string(),'Availability settings expose the WordPress timezone.');
      $state=$settings->activeState();Assert::true($state['weekend']&&$state['holiday'],'Current WordPress-local time matches configured overlapping periods.');
      Assert::equals(2,count($settings->activeNotices()),'Overlapping weekend and holiday portal notices are both returned.');
    }finally{update_option('sbay_weekend_holiday_settings',is_array($previous)?$previous:[],false);}
  }
}
