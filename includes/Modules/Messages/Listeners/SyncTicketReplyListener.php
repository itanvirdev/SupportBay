<?php

declare(strict_types=1);

namespace SupportBay\Modules\Messages\Listeners;

use SupportBay\Core\Events\Contracts\Event;
use SupportBay\Core\Events\Contracts\Listener;
use SupportBay\Modules\Messages\Events\MessageCreated;
use SupportBay\Modules\Tickets\Repositories\TicketRepository;

final class SyncTicketReplyListener implements Listener {
  public function __construct(
    private readonly TicketRepository $tickets
  ) {
  }

  /**
   * Sync ticket after a new message.
   */
  public function handle(Event $event): void {
    /** @var MessageCreated $event */

    $message = $event->message();

    $this->tickets->update(
      $message->ticketId(),
      [
        'last_message_id' => $message->id(),
        'last_reply_at'   => current_time('mysql'),
      ]
    );
  }
}
