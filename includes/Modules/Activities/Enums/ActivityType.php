<?php

declare(strict_types=1);

namespace SupportBay\Modules\Activities\Enums;

enum ActivityType: string {
  case TICKET_CREATED      = 'ticket_created';
  case TICKET_UPDATED      = 'ticket_updated';

  case TICKET_ASSIGNED     = 'ticket_assigned';
  case TICKET_UNASSIGNED   = 'ticket_unassigned';

  case STATUS_CHANGED      = 'status_changed';
  case STATE_CHANGED       = 'state_changed';
  case PRIORITY_CHANGED    = 'priority_changed';

  case DEPARTMENT_CHANGED  = 'department_changed';

  case PURCHASE_VERIFIED   = 'purchase_verified';

  case TICKET_REOPENED     = 'ticket_reopened';
  case TICKET_RESOLVED     = 'ticket_resolved';
  case TICKET_CLOSED       = 'ticket_closed';

  case MESSAGE_CREATED     = 'message_created';
  case MESSAGE_EDITED      = 'message_edited';

  case ATTACHMENT_UPLOADED = 'attachment_uploaded';
  case ATTACHMENT_DELETED  = 'attachment_deleted';

  /**
   * Human-readable label.
   */
  public function label(): string {
    return match ($this) {
      self::TICKET_CREATED      => 'Ticket Created',
      self::TICKET_UPDATED      => 'Ticket Updated',

      self::TICKET_ASSIGNED     => 'Ticket Assigned',
      self::TICKET_UNASSIGNED   => 'Ticket Unassigned',

      self::STATUS_CHANGED      => 'Status Changed',
      self::STATE_CHANGED       => 'State Changed',
      self::PRIORITY_CHANGED    => 'Priority Changed',

      self::DEPARTMENT_CHANGED  => 'Department Changed',

      self::PURCHASE_VERIFIED   => 'Purchase Verified',

      self::TICKET_REOPENED     => 'Ticket Reopened',
      self::TICKET_RESOLVED     => 'Ticket Resolved',
      self::TICKET_CLOSED       => 'Ticket Closed',

      self::MESSAGE_CREATED     => 'Message Created',
      self::MESSAGE_EDITED      => 'Message Edited',

      self::ATTACHMENT_UPLOADED => 'Attachment Uploaded',
      self::ATTACHMENT_DELETED  => 'Attachment Deleted',
    };
  }
}
