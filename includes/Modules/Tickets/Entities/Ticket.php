<?php

declare(strict_types=1);

namespace SupportBay\Modules\Tickets\Entities;

use SupportBay\Modules\Tickets\Enums\MessageType;
use SupportBay\Common\Enums\AuthorType;
use SupportBay\Core\Entities\Entity;

final class Ticket extends Entity {

  public function toArray(): array {
    return [
      'id' => $this->id,
      'track_id' => $this->trackId,
      'customer_id' => $this->customerId,
      'created_by_id' => $this->createdById,
      'created_by_type' => $this->createdByType->value,
      'purchase_verification_id' => $this->purchaseVerificationId,
      'department_id' => $this->departmentId,
      'assigned_agent_id' => $this->assignedAgentId,
      'subject' => $this->subject,
      'status' => $this->status->value,
      'state' => $this->state->value,
      'priority' => $this->priority->value,
      'source' => $this->source->value,
      'last_message_id' => $this->lastMessageId,
      'last_reply_at' => $this->lastReplyAt,
      'first_response_at' => $this->firstResponseAt,
      'resolved_at' => $this->resolvedAt,
      'closed_at' => $this->closedAt,
      'reopened_at' => $this->reopenedAt,
      'is_public' => $this->isPublic,
      'public_token' => $this->publicToken,
      'metadata' => $this->metadata,
      'created_at' => $this->createdAt,
      'updated_at' => $this->updatedAt,
    ];
  }
}
