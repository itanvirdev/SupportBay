<?php

declare(strict_types=1);

namespace SupportBay\Modules\Portal\Services;

use InvalidArgumentException;
use RuntimeException;
use SupportBay\Common\Enums\AuthorType;
use SupportBay\Common\Enums\SourceType;
use SupportBay\Modules\Attachments\Entities\Attachment;
use SupportBay\Modules\Attachments\Services\AttachmentService;
use SupportBay\Modules\Customers\Entities\Customer;
use SupportBay\Modules\Customers\Services\CustomerService;
use SupportBay\Modules\Departments\Entities\Department;
use SupportBay\Modules\Departments\Services\DepartmentService;
use SupportBay\Modules\Messages\Entities\Message;
use SupportBay\Modules\Messages\Enums\MessageType;
use SupportBay\Modules\Messages\Services\MessageService;
use SupportBay\Modules\Tickets\Entities\Ticket;
use SupportBay\Modules\Tickets\Services\TicketService;
use SupportBay\Modules\Verifications\Services\VerificationService;
use SupportBay\Modules\Verifications\Entities\Verification;

final class PortalService {
  public function __construct(
    private readonly CustomerService $customers,
    private readonly TicketService $tickets,
    private readonly VerificationService $verifications,
    private readonly MessageService $messages,
    private readonly DepartmentService $departments,
    private readonly AttachmentService $attachments,
  ) {
  }

  /**
   * Resolve the authenticated SupportBay customer.
   */
  public function currentCustomer(): Customer {
    $userId = get_current_user_id();

    if ($userId <= 0) {
      throw new RuntimeException(
        'Authentication is required.'
      );
    }

    $customer = $this->customers->findByUser($userId);

    if (! $customer) {
      throw new RuntimeException(
        'The authenticated user is not a SupportBay customer.'
      );
    }

    return $customer;
  }

  /**
   * Get the current customer's tickets.
   *
   * @return array<int, \SupportBay\Modules\Tickets\Entities\Ticket>
   */
  public function tickets(): array {
    return $this->tickets->findByCustomer(
      $this->currentCustomer()->id()
    );
  }

  /**
   * Get the current customer's verifications.
   *
   * @return array<int, \SupportBay\Modules\Verifications\Entities\Verification>
   */
  public function verifications(): array {
    return $this->verifications->findByCustomer(
      $this->currentCustomer()->id()
    );
  }

  /**
   * Resolve a current-customer verification by ID.
   */
  public function verification(
    int $verificationId,
  ): ?Verification {
    foreach ($this->verifications() as $verification) {
      if ($verification->id() === $verificationId) {
        return $verification;
      }
    }

    return null;
  }

  /**
   * Resolve a ticket owned by the current customer.
   */
  public function ticket(int $ticketId): Ticket {
    $ticket = $this->tickets->find($ticketId);
    $customer = $this->currentCustomer();

    if (
      ! $ticket ||
      $ticket->customerId() !== $customer->id() ||
      ! $ticket->state()->isAccessible()
    ) {
      throw new RuntimeException(
        'Ticket was not found.'
      );
    }

    return $ticket;
  }

  /**
   * Get customer-visible messages for an owned ticket.
   *
   * @return Message[]
   */
  public function ticketMessages(int $ticketId): array {
    $this->ticket($ticketId);

    return array_values(array_filter(
      $this->messages->findByTicket($ticketId),
      fn(Message $message): bool => $message->isVisibleToCustomer(),
    ));
  }

  /**
   * Get departments available for customer tickets.
   *
   * @return Department[]
   */
  public function departments(): array {
    return $this->departments->active();
  }

  /**
   * Create a customer ticket with its opening message.
   *
   * @param array<string, mixed> $data
   */
  public function createTicket(array $data): Ticket {
    $customer = $this->currentCustomer();
    $departmentId = (int) ($data['department_id'] ?? 0);
    $department = $this->departments->find($departmentId);

    if (! $department || ! $department->isActive()) {
      throw new InvalidArgumentException(
        'Please select an available department.'
      );
    }

    $subject = trim((string) ($data['subject'] ?? ''));
    $content = trim((string) ($data['content'] ?? ''));

    if ($subject === '') {
      throw new InvalidArgumentException('Ticket subject is required.');
    }

    if ($content === '') {
      throw new InvalidArgumentException('Opening message is required.');
    }

    $verificationId = ! empty($data['purchase_verification_id'])
      ? (int) $data['purchase_verification_id']
      : null;

    if ($verificationId !== null && ! $this->verification($verificationId)) {
      throw new InvalidArgumentException(
        'The selected purchase is unavailable.'
      );
    }

    $ticketId = $this->tickets->create([
      'customer_id'              => $customer->id(),
      'created_by_id'            => $customer->userId(),
      'created_by_type'          => AuthorType::CUSTOMER->value,
      'purchase_verification_id' => $verificationId,
      'department_id'            => $departmentId,
      'subject'                  => $subject,
      'priority'                 => $department->defaultPriority()->value,
      'source'                   => SourceType::WEB->value,
    ]);

    try {
      $this->messages->create([
        'ticket_id'   => $ticketId,
        'author_id'   => $customer->userId(),
        'author_type' => AuthorType::CUSTOMER->value,
        'type'        => MessageType::REPLY->value,
        'content'     => $content,
      ]);
    } catch (RuntimeException $exception) {
      $this->tickets->delete($ticketId);

      throw $exception;
    }

    $ticket = $this->tickets->find($ticketId);

    if (! $ticket) {
      throw new RuntimeException('Failed to create ticket.');
    }

    return $ticket;
  }

  /**
   * Add a customer reply to an owned ticket.
   */
  public function reply(int $ticketId, string $content): Message {
    $ticket = $this->ticket($ticketId);
    $customer = $this->currentCustomer();

    if (! $ticket->status()->canReceiveReplies()) {
      throw new RuntimeException(
        'This ticket cannot receive new replies.'
      );
    }

    if (trim($content) === '') {
      throw new InvalidArgumentException('Reply content is required.');
    }

    return $this->messages->create([
      'ticket_id'   => $ticket->id(),
      'author_id'   => $customer->userId(),
      'author_type' => AuthorType::CUSTOMER->value,
      'type'        => MessageType::REPLY->value,
      'content'     => $content,
    ]);
  }

  /**
   * Get active attachments belonging to a customer-visible message.
   *
   * @return Attachment[]
   */
  public function messageAttachments(Message $message): array {
    return array_values(array_filter(
      $this->attachments->findByMessage($message->id()),
      fn(Attachment $attachment): bool => $attachment->isActive(),
    ));
  }

  /**
   * Upload a file to a customer-owned, customer-visible message.
   *
   * @param array<string, mixed> $file
   */
  public function uploadAttachment(
    int $ticketId,
    int $messageId,
    array $file,
  ): Attachment {
    $ticket = $this->ticket($ticketId);
    $message = $this->messages->find($messageId);
    $customer = $this->currentCustomer();

    if (
      ! $message ||
      $message->ticketId() !== $ticket->id() ||
      ! $message->isVisibleToCustomer()
    ) {
      throw new RuntimeException('Message was not found.');
    }

    if (count($this->messageAttachments($message)) >= 5) {
      throw new InvalidArgumentException(
        'A message can have up to five attachments.'
      );
    }

    return $this->attachments->storeUploadedFile($file, [
      'message_id'       => $message->id(),
      'ticket_id'        => $ticket->id(),
      'uploaded_by_id'   => $customer->userId(),
      'uploaded_by_type' => AuthorType::CUSTOMER->value,
    ]);
  }

  /**
   * Close a ticket owned by the current customer.
   */
  public function closeTicket(int $ticketId): Ticket {
    $this->ticket($ticketId);

    return $this->tickets->close($ticketId);
  }

  /**
   * Reopen a closed ticket owned by the current customer.
   */
  public function reopenTicket(int $ticketId): Ticket {
    $this->ticket($ticketId);

    return $this->tickets->reopen($ticketId);
  }

  /**
   * Resolve an attachment the current customer may download.
   */
  public function downloadableAttachment(int $attachmentId): Attachment {
    $attachment = $this->attachments->find($attachmentId);

    if (
      ! $attachment ||
      ! $attachment->isActive() ||
      ! $attachment->isStoredLocally() ||
      $attachment->isInfected()
    ) {
      throw new RuntimeException('Attachment was not found.');
    }

    $ticket = $this->ticket($attachment->ticketId());
    $message = $this->messages->find($attachment->messageId());

    if (
      ! $message ||
      $message->ticketId() !== $ticket->id() ||
      ! $message->isVisibleToCustomer() ||
      ! is_file($attachment->path()) ||
      ! is_readable($attachment->path()) ||
      (
        $attachment->hasChecksum() &&
        ! hash_equals(
          (string) $attachment->checksum(),
          (string) hash_file('sha256', $attachment->path()),
        )
      )
    ) {
      throw new RuntimeException('Attachment was not found.');
    }

    return $attachment;
  }

  /**
   * Record a successful customer attachment download.
   */
  public function recordAttachmentDownload(int $attachmentId): void {
    $this->attachments->recordDownload($attachmentId);
  }
}
