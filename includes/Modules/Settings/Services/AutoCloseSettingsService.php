<?php
declare(strict_types=1);
namespace SupportBay\Modules\Settings\Services;
use InvalidArgumentException;
use SupportBay\Modules\Settings\Repositories\AutoCloseSettingsRepository;
use SupportBay\Modules\Tags\Services\TagService;
final class AutoCloseSettingsService {
  public function __construct(private readonly AutoCloseSettingsRepository $repository,private readonly TagService $tags){}
  /** @return array<string,mixed> */
  public function get():array{$saved=$this->repository->all();return[
    'auto_close_enabled'=>(bool)($saved['auto_close_enabled']??false),'close_after_days'=>$this->days($saved['close_after_days']??30),'excluded_tag_ids'=>$this->tagIds($saved['excluded_tag_ids']??[]),
    'auto_trash_enabled'=>(bool)($saved['auto_trash_enabled']??false),'trash_after_days'=>$this->days($saved['trash_after_days']??30),
    'auto_delete_enabled'=>(bool)($saved['auto_delete_enabled']??false),'delete_after_days'=>$this->days($saved['delete_after_days']??30),
    'tag_options'=>array_map(static fn($tag):array=>['id'=>$tag->id(),'name'=>$tag->name()],$this->tags->active()),
  ];}
  /** @param array<string,mixed> $data @return array<string,mixed> */
  public function update(array $data):array{$settings=$this->get();foreach(['auto_close_enabled','auto_trash_enabled','auto_delete_enabled']as$key)if(array_key_exists($key,$data))$settings[$key]=filter_var($data[$key],FILTER_VALIDATE_BOOL);foreach(['close_after_days','trash_after_days','delete_after_days']as$key)if(array_key_exists($key,$data))$settings[$key]=$this->days($data[$key]);if(array_key_exists('excluded_tag_ids',$data))$settings['excluded_tag_ids']=$this->tagIds($data['excluded_tag_ids']);unset($settings['tag_options']);$this->repository->save($settings);return$this->get();}
  private function days(mixed $value):int{$days=absint($value);if($days<1||$days>3650)throw new InvalidArgumentException('Lifecycle intervals must be between 1 and 3650 days.');return$days;}
  /** @return int[] */ private function tagIds(mixed $value):array{if(!is_array($value))throw new InvalidArgumentException('Excluded tags must be a list.');return array_values(array_unique(array_filter(array_map('absint',$value))));}
}
