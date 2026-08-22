<?php

declare(strict_types=1);

namespace SupportBay\Modules\Settings\Services;

use DateTimeImmutable;
use InvalidArgumentException;
use SupportBay\Modules\Settings\Repositories\WeekendHolidaySettingsRepository;

final class WeekendHolidaySettingsService {
  private const WEEKEND_NOTICE = 'Please note that our support team is currently out of office for the weekend. We will respond to your inquiry as soon as possible when we return. Thank you for your patience.';
  private const HOLIDAY_NOTICE = 'Please note that our support team is currently out of office for a holiday. We will respond to your inquiry as soon as possible when we return. Thank you for your patience.';
  private const WEEKEND_EMAIL = "Hi {{ticket_user}},\n\nThank you for reaching out to us. Please note that our support team is currently off for the weekend. We’ve received your ticket and will get back to you as soon as our team returns.\n\nThank you for your patience!\n\n— The {{site_name}} Support Team";
  private const HOLIDAY_EMAIL = "Hi {{ticket_user}},\n\nThank you for reaching out to us. Our support team is currently away for a holiday. We’ve received your ticket and will respond as soon as our team returns.\n\nThank you for your patience!\n\n— The {{site_name}} Support Team";

  public function __construct(private readonly WeekendHolidaySettingsRepository $repository) {}

  /** @return array<string, mixed> */
  public function get(): array {
    $saved=$this->repository->all();
    return [
      'weekend_enabled'=>(bool)($saved['weekend_enabled']??false),
      'weekend_days'=>$this->normalizeDays($saved['weekend_days']??[['day'=>6,'all_day'=>true,'start'=>'','end'=>''],['day'=>0,'all_day'=>true,'start'=>'','end'=>'']]),
      'weekend_portal_notice_enabled'=>(bool)($saved['weekend_portal_notice_enabled']??true),
      'weekend_portal_notice'=>sanitize_textarea_field((string)($saved['weekend_portal_notice']??self::WEEKEND_NOTICE)),
      'weekend_email_enabled'=>(bool)($saved['weekend_email_enabled']??false),
      'weekend_email_content'=>sanitize_textarea_field((string)($saved['weekend_email_content']??self::WEEKEND_EMAIL)),
      'holiday_enabled'=>(bool)($saved['holiday_enabled']??false),
      'holidays'=>$this->normalizeHolidays($saved['holidays']??[]),
      'holiday_portal_notice_enabled'=>(bool)($saved['holiday_portal_notice_enabled']??true),
      'holiday_portal_notice'=>sanitize_textarea_field((string)($saved['holiday_portal_notice']??self::HOLIDAY_NOTICE)),
      'holiday_email_enabled'=>(bool)($saved['holiday_email_enabled']??false),
      'holiday_email_content'=>sanitize_textarea_field((string)($saved['holiday_email_content']??self::HOLIDAY_EMAIL)),
      'timezone'=>wp_timezone_string(),
    ];
  }

  /** @param array<string,mixed> $data @return array<string,mixed> */
  public function update(array $data): array {
    $settings=$this->get();
    foreach(['weekend_enabled','weekend_portal_notice_enabled','weekend_email_enabled','holiday_enabled','holiday_portal_notice_enabled','holiday_email_enabled'] as $key){if(array_key_exists($key,$data))$settings[$key]=filter_var($data[$key],FILTER_VALIDATE_BOOL);}
    foreach(['weekend_portal_notice','weekend_email_content','holiday_portal_notice','holiday_email_content'] as $key){if(array_key_exists($key,$data))$settings[$key]=sanitize_textarea_field((string)$data[$key]);}
    if(array_key_exists('weekend_days',$data))$settings['weekend_days']=$this->normalizeDays($data['weekend_days']);
    if(array_key_exists('holidays',$data))$settings['holidays']=$this->normalizeHolidays($data['holidays']);
    unset($settings['timezone']);$this->repository->save($settings);return $this->get();
  }

  /** @return array<int,array{type:string,message:string}> */
  public function activeNotices(): array {
    $settings=$this->get();$state=$this->activeState($settings);$notices=[];
    if($state['weekend']&&$settings['weekend_portal_notice_enabled'])$notices[]=['type'=>'weekend','message'=>$settings['weekend_portal_notice']];
    if($state['holiday']&&$settings['holiday_portal_notice_enabled'])$notices[]=['type'=>'holiday','message'=>$settings['holiday_portal_notice']];
    return $notices;
  }

  /** @return array{weekend:bool,holiday:bool} */
  public function activeState(?array $settings=null): array {
    $settings??=$this->get();$now=current_datetime();$day=(int)$now->format('w');$time=$now->format('H:i');$date=$now->format('Y-m-d');
    $weekend=false;if($settings['weekend_enabled'])foreach($settings['weekend_days'] as $period){if($period['day']!==$day)continue;if($period['all_day']||($period['start']<=$time&&$time<=$period['end'])){$weekend=true;break;}}
    $holiday=false;if($settings['holiday_enabled'])foreach($settings['holidays'] as $range){if($range['start_date']<=$date&&$date<=$range['end_date']){$holiday=true;break;}}
    return ['weekend'=>$weekend,'holiday'=>$holiday];
  }

  /** @return array<int,array{day:int,all_day:bool,start:string,end:string}> */
  private function normalizeDays(mixed $rows): array {
    if(!is_array($rows))throw new InvalidArgumentException('Weekend days must be a list.');$result=[];
    foreach($rows as $row){if(!is_array($row))continue;$day=(int)($row['day']??-1);if($day<0||$day>6)throw new InvalidArgumentException('Please select a valid weekend day.');$all=filter_var($row['all_day']??false,FILTER_VALIDATE_BOOL);$start=$this->time($row['start']??'');$end=$this->time($row['end']??'');if(!$all&&($start===''||$end===''||$start>$end))throw new InvalidArgumentException('Weekend time ranges must have a valid start and end time.');$result[]=['day'=>$day,'all_day'=>$all,'start'=>$all?'':$start,'end'=>$all?'':$end];}
    return $result;
  }

  /** @return array<int,array{name:string,start_date:string,end_date:string}> */
  private function normalizeHolidays(mixed $rows): array {
    if(!is_array($rows))throw new InvalidArgumentException('Holidays must be a list.');$result=[];
    foreach($rows as $row){if(!is_array($row))continue;$name=sanitize_text_field((string)($row['name']??''));$start=$this->date($row['start_date']??'');$end=$this->date($row['end_date']??'');if($name===''||$start===''||$end===''||$start>$end)throw new InvalidArgumentException('Each holiday needs a name and valid date range.');$result[]=['name'=>$name,'start_date'=>$start,'end_date'=>$end];}
    return $result;
  }
  private function time(mixed $value): string {$value=sanitize_text_field((string)$value);return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/',$value)?$value:'';}
  private function date(mixed $value): string {$value=sanitize_text_field((string)$value);$date=DateTimeImmutable::createFromFormat('!Y-m-d',$value,wp_timezone());return $date&&$date->format('Y-m-d')===$value?$value:'';}
}
