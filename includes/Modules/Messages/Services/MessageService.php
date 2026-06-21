<?php

declare(strict_types=1);

namespace SupportBay\Modules\Messages\Services;

use SupportBay\Modules\Messages\Repositories\MessageRepository;
use SupportBay\Modules\Messages\Entities\Message;
use SupportBay\Modules\Messages\Enums\MessageType;
use SupportBay\Common\Enums\AuthorType;
use InvalidArgumentException;
use RuntimeException;

final class MessageService {
  public function __construct(
    private MessageRepository $repository
  ) {
  }

  /**
   * Create a message (FULL DOMAIN FLOW)
   */
  public function create(array $data): Message {
    $this->validate($data);

    $data = $this->normalize($data);

    // 1. Persist message → now returns ID
    $messageId = $this->repository->create($data);

    // 2. Re-fetch as ENTITY (important upgrade)
    $message = $this->repository->find($messageId);

    if (!$message) {
      throw new RuntimeException('Failed to create message entity.');
    }

    // 3. Domain orchestration using ENTITY
    $this->syncTicket($message);
    $this->handleReadState($message);
    $this->triggerEvents($message);

    return $message;
  }

  /**
   * Validation layer
   */
  private function validate(array $data): void {
    if (empty($data['ticket_id'])) {
      throw new InvalidArgumentException('Ticket ID is required.');
    }

    if (empty($data['content'])) {
      throw new InvalidArgumentException('Message content is required.');
    }
  }

  /**
   * Normalize input
   */
  private function normalize(array $data): array {
    $data['type'] ??= MessageType::default()->value;
    $data['author_type'] ??= AuthorType::default()->value;

    $data['content'] = wp_kses_post($data['content']);

    return $data;
  }

  /**
   * Sync ticket using ENTITY (not array)
   */
  private function syncTicket(Message $message): void {
    global $wpdb;

    $table = $wpdb->prefix . 'sbay_tickets';

    $update = [
      'last_message_id' => $message->id(),
      'updated_at'      => current_time('mysql'),
    ];

    // Only update reply timestamp if visible message
    if (!$message->type()->isEditable() || !$message->isSystem()) {
      $update['last_reply_at'] = current_time('mysql');
    }

    $wpdb->update(
      $table,
      $update,
      ['id' => $message->ticketId()]
    );
  }

  /**
   * Handle read state using ENTITY
   */
  private function handleReadState(Message $message): void {
    if ($message->isFromCustomer()) {
      $this->repository->markCustomerRead($message->id());
    } else {
      $this->repository->markStaffRead($message->id());
    }
  }

  /**
   * Trigger side effects (future system)
   */
  private function triggerEvents(Message $message): void {
    // FUTURE:
    // - NotificationService
    // - ActivityLogger
    // - Webhooks
    // - Email triggers

    // Example hooks:
    // do_action('supportbay.message.created', $message);
  }
}
