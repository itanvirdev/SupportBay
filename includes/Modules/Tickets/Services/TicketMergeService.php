<?php

declare(strict_types=1);

namespace SupportBay\Modules\Tickets\Services;

use RuntimeException;
use SupportBay\Core\Database\TransactionManager;
use SupportBay\Core\Events\EventDispatcher;
use SupportBay\Modules\Attachments\Repositories\AttachmentRepository;
use SupportBay\Modules\Messages\Enums\MessageType;
use SupportBay\Modules\Messages\Repositories\MessageRepository;
use SupportBay\Modules\Tickets\Entities\Ticket;
use SupportBay\Modules\Tickets\Enums\TicketState;
use SupportBay\Modules\Tickets\Events\TicketMerged;
use SupportBay\Modules\Tickets\Repositories\TicketRepository;

final class TicketMergeService {
  public function __construct(
    private readonly TicketRepository $tickets,
    private readonly MessageRepository $messages,
    private readonly AttachmentRepository $attachments,
    private readonly TransactionManager $transactions,
    private readonly EventDispatcher $events,
  ) {
  }

  public function merge(int $sourceId, int $targetId, int $actorId): Ticket {
    if ($sourceId === $targetId) {
      throw new RuntimeException('A ticket cannot be merged into itself.');
    }

    $source = $this->findOrFail($sourceId);
    $target = $this->findOrFail($targetId);
    $sourceCustomerId = $source->toArray()['customer_id'];
    $targetCustomerId = $target->toArray()['customer_id'];

    if ($source->state() === TicketState::TRASH) {
      throw new RuntimeException('A trashed ticket cannot be merged.');
    }

    if ($target->state() === TicketState::TRASH) {
      throw new RuntimeException('A ticket cannot be merged into a trashed ticket.');
    }

    if ($sourceCustomerId !== $targetCustomerId) {
      throw new RuntimeException('Only tickets belonging to the same customer can be merged.');
    }

    $this->transactions->run(function () use ($source, $target): void {
      $this->messages->moveToTicket($source->id(), $target->id());
      $this->attachments->moveToTicket($source->id(), $target->id());

      $messages = $this->messages->getByTicket($target->id());
      $lastMessage = $messages !== [] ? $messages[array_key_last($messages)] : null;
      $lastReplyAt = null;

      foreach (array_reverse($messages) as $message) {
        if ($message->type() === MessageType::REPLY) {
          $lastReplyAt = $message->createdAt();
          break;
        }
      }

      $this->tickets->update($target->id(), [
        'last_message_id' => $lastMessage?->id(),
        'last_reply_at' => $lastReplyAt,
        'metadata' => $this->mergedMetadata($target->metadata(), 'merged_ticket_ids', $source->id()),
      ]);
      $this->tickets->update($source->id(), [
        'state' => TicketState::TRASH->value,
        'last_message_id' => null,
        'last_reply_at' => null,
        'metadata' => $this->mergedMetadata($source->metadata(), 'merged_into_id', $target->id()),
      ]);
    });

    $mergedSource = $this->findOrFail($sourceId);
    $mergedTarget = $this->findOrFail($targetId);
    $this->events->dispatch(new TicketMerged($mergedSource, $mergedTarget, $actorId));

    return $mergedTarget;
  }

  private function findOrFail(int $id): Ticket {
    $ticket = $this->tickets->find($id);

    if (! $ticket) {
      throw new RuntimeException('Ticket was not found.');
    }

    return $ticket;
  }

  private function mergedMetadata(?string $metadata, string $key, int $value): string {
    $decoded = $metadata ? json_decode($metadata, true) : [];
    $data = is_array($decoded) ? $decoded : [];

    if ($key === 'merged_ticket_ids') {
      $values = array_map('absint', (array) ($data[$key] ?? []));
      $data[$key] = array_values(array_unique([...$values, $value]));
    } else {
      $data[$key] = $value;
    }

    return (string) wp_json_encode($data);
  }
}
