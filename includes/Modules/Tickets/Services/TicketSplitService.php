<?php

declare(strict_types=1);

namespace SupportBay\Modules\Tickets\Services;

use InvalidArgumentException;
use RuntimeException;
use SupportBay\Common\Enums\AuthorType;
use SupportBay\Core\Database\TransactionManager;
use SupportBay\Core\Events\EventDispatcher;
use SupportBay\Modules\Attachments\Repositories\AttachmentRepository;
use SupportBay\Modules\Messages\Entities\Message;
use SupportBay\Modules\Messages\Enums\MessageType;
use SupportBay\Modules\Messages\Repositories\MessageRepository;
use SupportBay\Modules\Tickets\Entities\Ticket;
use SupportBay\Modules\Tickets\Events\TicketSplit;
use SupportBay\Modules\Tickets\Enums\TicketState;
use SupportBay\Modules\Tickets\Enums\TicketStatus;
use SupportBay\Modules\Tickets\Repositories\TicketRepository;

final class TicketSplitService {
  public function __construct(
    private readonly TicketRepository $tickets,
    private readonly MessageRepository $messages,
    private readonly AttachmentRepository $attachments,
    private readonly TransactionManager $transactions,
    private readonly EventDispatcher $events,
    private readonly TicketTrackIdService $trackIds,
  ) {
  }

  /** @param int[] $messageIds */
  public function split(int $sourceId, array $messageIds, string $subject, int $actorId): Ticket {
    $source = $this->findOrFail($sourceId);
    $subject = sanitize_text_field($subject);
    $selectedIds = array_values(array_unique(array_filter(array_map('absint', $messageIds))));
    $sourceMessages = $this->messages->getByTicket($sourceId);
    $sourceMessageIds = array_map(static fn(Message $message): int => $message->id(), $sourceMessages);

    if ($source->state() === TicketState::TRASH) {
      throw new InvalidArgumentException('A trashed ticket cannot be split.');
    }

    if ($subject === '') {
      throw new InvalidArgumentException('The new ticket subject is required.');
    }

    if ($selectedIds === [] || count($selectedIds) >= count($sourceMessageIds)) {
      throw new InvalidArgumentException('Select at least one, but not all, conversation entries.');
    }

    if (array_diff($selectedIds, $sourceMessageIds) !== []) {
      throw new InvalidArgumentException('One or more selected messages do not belong to this ticket.');
    }

    $newTicketId = $this->transactions->run(function () use ($source, $selectedIds, $subject, $actorId): int {
      $sourceData = $source->toArray();
      $newTicketId = $this->tickets->create([
        'track_id' => $this->trackIds->next(),
        'customer_id' => $sourceData['customer_id'],
        'created_by_id' => $actorId,
        'created_by_type' => AuthorType::AGENT->value,
        'purchase_verification_id' => $source->purchaseVerificationId(),
        'department_id' => $source->departmentId(),
        'assigned_agent_id' => $source->assignedAgentId(),
        'subject' => $subject,
        'status' => TicketStatus::OPEN->value,
        'state' => TicketState::ACTIVE->value,
        'priority' => $source->priority()->value,
        'source' => $source->source()->value,
        'metadata' => (string) wp_json_encode(['split_from_id' => $source->id()]),
      ]);

      $moved = $this->messages->moveSelectedToTicket($selectedIds, $source->id(), $newTicketId);

      if ($moved !== count($selectedIds)) {
        throw new RuntimeException('Not all selected messages could be moved.');
      }

      $this->attachments->moveByMessagesToTicket($selectedIds, $source->id(), $newTicketId);
      $this->repairQueue($source->id());
      $this->repairQueue($newTicketId);
      $this->tickets->update($source->id(), [
        'metadata' => $this->appendSplitMetadata($source->metadata(), $newTicketId),
      ]);

      return $newTicketId;
    });

    $updatedSource = $this->findOrFail($sourceId);
    $created = $this->findOrFail($newTicketId);
    $this->events->dispatch(new TicketSplit($updatedSource, $created, $actorId));

    return $created;
  }

  private function repairQueue(int $ticketId): void {
    $messages = $this->messages->getByTicket($ticketId);
    $lastMessage = $messages !== [] ? $messages[array_key_last($messages)] : null;
    $lastReplyAt = null;

    foreach (array_reverse($messages) as $message) {
      if ($message->type() === MessageType::REPLY) {
        $lastReplyAt = $message->createdAt();
        break;
      }
    }

    $this->tickets->update($ticketId, [
      'last_message_id' => $lastMessage?->id(),
      'last_reply_at' => $lastReplyAt,
    ]);
  }

  private function findOrFail(int $id): Ticket {
    $ticket = $this->tickets->find($id);

    if (! $ticket) {
      throw new RuntimeException('Ticket was not found.');
    }

    return $ticket;
  }

  private function appendSplitMetadata(?string $metadata, int $ticketId): string {
    $decoded = $metadata ? json_decode($metadata, true) : [];
    $data = is_array($decoded) ? $decoded : [];
    $ids = array_map('absint', (array) ($data['split_ticket_ids'] ?? []));
    $data['split_ticket_ids'] = array_values(array_unique([...$ids, $ticketId]));

    return (string) wp_json_encode($data);
  }
}
