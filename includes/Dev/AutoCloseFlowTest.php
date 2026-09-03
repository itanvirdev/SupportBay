<?php
declare(strict_types=1);
namespace SupportBay\Dev;
use SupportBay\Core\Testing\Assert;use SupportBay\Core\Testing\FlowTest;use SupportBay\Modules\Settings\Services\AutoCloseSettingsService;use SupportBay\Modules\Tickets\Enums\TicketState;use SupportBay\Modules\Tickets\Enums\TicketStatus;use SupportBay\Modules\Tickets\Repositories\TicketRepository;use SupportBay\Modules\Tickets\Services\TicketLifecycleWorker;use SupportBay\Modules\Tickets\Services\TicketService;
final class AutoCloseFlowTest extends FlowTest{
  protected static function title():string{return'Auto Close & Delete Flow Test';}
  protected static function execute(...$services):void{/** @var TicketService $tickets */[$tickets,$repository,$worker,$settings]=$services;$previous=get_option('sbay_auto_close_settings',[]);$id=$tickets->create(['customer_id'=>1,'subject'=>'Auto lifecycle flow test']);$old=current_datetime()->modify('-2 days')->format('Y-m-d H:i:s');
    try{$settings->update(['auto_close_enabled'=>true,'close_after_days'=>1,'auto_trash_enabled'=>false,'auto_delete_enabled'=>false]);$repository->update($id,['created_at'=>$old,'updated_at'=>$old]);Assert::true($worker->run()['closed']>=1,'Inactive ticket is automatically closed.');Assert::equals(TicketStatus::CLOSED,$tickets->find($id)?->status(),'Auto-close changes ticket status to Closed.');
      $settings->update(['auto_close_enabled'=>false,'auto_trash_enabled'=>true,'trash_after_days'=>1]);$repository->update($id,['closed_at'=>$old,'updated_at'=>$old]);Assert::true($worker->run()['trashed']>=1,'Closed ticket is automatically trashed.');Assert::equals(TicketState::TRASH,$tickets->find($id)?->state(),'Auto-trash remains recoverable before deletion.');
      $settings->update(['auto_trash_enabled'=>false,'auto_delete_enabled'=>true,'delete_after_days'=>1]);$repository->update($id,['updated_at'=>$old]);Assert::true($worker->run()['deleted']>=1,'Eligible trashed ticket is permanently deleted.');Assert::true($tickets->find($id)===null,'Permanent deletion removes the ticket.');
    }finally{if($tickets->find($id)){$repository->delete($id);}update_option('sbay_auto_close_settings',is_array($previous)?$previous:[],false);}
  }
}
