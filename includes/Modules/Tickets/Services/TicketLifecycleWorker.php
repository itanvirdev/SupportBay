<?php
declare(strict_types=1);
namespace SupportBay\Modules\Tickets\Services;
use SupportBay\Modules\Activities\Repositories\ActivityRepository;use SupportBay\Modules\Attachments\Services\AttachmentService;use SupportBay\Modules\CustomFields\Repositories\CustomFieldRepository;use SupportBay\Modules\Messages\Repositories\MessageRepository;use SupportBay\Modules\Settings\Services\AutoCloseSettingsService;use SupportBay\Modules\Tags\Repositories\TagRepository;use SupportBay\Modules\Tickets\Enums\TicketState;use SupportBay\Modules\Tickets\Repositories\TicketRepository;
final class TicketLifecycleWorker{
  public const HOOK='sbay_ticket_lifecycle_cleanup';
  public function __construct(private readonly AutoCloseSettingsService $settings,private readonly TicketRepository $tickets,private readonly TicketService $service,private readonly AttachmentService $attachments,private readonly MessageRepository $messages,private readonly ActivityRepository $activities,private readonly TagRepository $tags,private readonly CustomFieldRepository $customFields){}
  public function register():void{add_action(self::HOOK,[$this,'run']);add_action('init',[$this,'ensureScheduled'],20);}
  public function ensureScheduled():void{if(wp_next_scheduled(self::HOOK)===false)wp_schedule_event((int)current_time('timestamp',true)+HOUR_IN_SECONDS,'daily',self::HOOK);}
  /** @return array{closed:int,trashed:int,deleted:int} */
  public function run():array{$settings=$this->settings->get();$result=['closed'=>0,'trashed'=>0,'deleted'=>0];
    if($settings['auto_close_enabled'])foreach($this->tickets->inactiveCandidateIds($this->cutoff($settings['close_after_days']),$settings['excluded_tag_ids'])as$id){try{$this->service->close($id);$result['closed']++;}catch(\RuntimeException){}}
    if($settings['auto_trash_enabled'])foreach($this->tickets->closedCandidateIds($this->cutoff($settings['trash_after_days']))as$id){try{$this->service->changeState($id,TicketState::TRASH,0);$result['trashed']++;}catch(\RuntimeException){}}
    if($settings['auto_delete_enabled'])foreach($this->tickets->trashedCandidateIds($this->cutoff($settings['delete_after_days']))as$id){if($this->permanentlyDelete($id))$result['deleted']++;}
    return$result;
  }
  public function permanentlyDelete(int $ticketId):bool{if($this->tickets->find($ticketId)?->state()!==TicketState::TRASH)return false;foreach($this->attachments->findByTicket($ticketId)as$attachment)$this->attachments->permanentlyDelete($attachment->id());$this->activities->deleteByTicket($ticketId);$this->customFields->deleteValuesForTicket($ticketId);$this->tags->deleteAssignmentsForTicket($ticketId);$this->messages->deleteByTicket($ticketId);return$this->tickets->delete($ticketId);}
  private function cutoff(int $days):string{return current_datetime()->modify('-'.$days.' days')->format('Y-m-d H:i:s');}
}
