<?php

declare(strict_types=1);

namespace SupportBay\Modules\Notifications\Templates;

use SupportBay\Modules\Notifications\Enums\NotificationRecipientType;
use SupportBay\Modules\Notifications\Enums\NotificationTemplateStatus;

final class DefaultNotificationTemplates {
  /** @return array<string, array<string, string>> */
  public function all(): array {
    return [
      'ticket_created:agent' => $this->template(
        'Agent — New Ticket',
        'ticket_created',
        NotificationRecipientType::AGENT,
        'New support ticket #{{track_id}}: {{ticket_subject}}',
        '<p>A new support ticket has been created.</p><p><strong>Ticket:</strong> #{{track_id}}<br><strong>Subject:</strong> {{ticket_subject}}</p><p><a href="{{ticket_url}}">View ticket</a></p>',
        "A new support ticket has been created.\n\nTicket: #{{track_id}}\nSubject: {{ticket_subject}}\nView: {{ticket_url}}",
      ),
      'ticket_created:customer' => $this->template(
        'Customer — Ticket Received',
        'ticket_created',
        NotificationRecipientType::CUSTOMER,
        'We received your ticket #{{track_id}}',
        '<p>Hello {{customer_name}},</p><p>We received your support ticket <strong>#{{track_id}}</strong>.</p><p><strong>Subject:</strong> {{ticket_subject}}</p><p><a href="{{ticket_url}}">View ticket</a></p>',
        "Hello {{customer_name}},\n\nWe received your support ticket #{{track_id}}.\nSubject: {{ticket_subject}}\nView: {{ticket_url}}",
      ),
      'customer_reply:agent' => $this->template(
        'Agent — New Customer Reply',
        'customer_reply',
        NotificationRecipientType::AGENT,
        'New reply on ticket #{{track_id}}: {{ticket_subject}}',
        '<p>A customer replied to ticket <strong>#{{track_id}}</strong>.</p><blockquote>{{reply_content}}</blockquote><p><a href="{{ticket_url}}">View ticket</a></p>',
        "A customer replied to ticket #{{track_id}}.\n\n{{reply_content}}\n\nView: {{ticket_url}}",
      ),
      'ticket_reply:customer' => $this->template(
        'Customer — New Agent Reply',
        'ticket_reply',
        NotificationRecipientType::CUSTOMER,
        'New reply on ticket #{{track_id}}: {{ticket_subject}}',
        '<p>Hello {{customer_name}},</p><p>A new reply was added to ticket <strong>#{{track_id}}</strong>.</p><blockquote>{{reply_content}}</blockquote><p><a href="{{ticket_url}}">View ticket</a></p>',
        "Hello {{customer_name}},\n\nA new reply was added to ticket #{{track_id}}.\n\n{{reply_content}}\n\nView: {{ticket_url}}",
      ),
      'ticket_closed:customer' => $this->template(
        'Customer — Ticket Closed',
        'ticket_closed',
        NotificationRecipientType::CUSTOMER,
        'Ticket #{{track_id}} has been closed',
        '<p>Hello {{customer_name}},</p><p>Your support ticket <strong>#{{track_id}}</strong> has been closed.</p><p><strong>Subject:</strong> {{ticket_subject}}</p><p><a href="{{ticket_url}}">View ticket</a></p>',
        "Hello {{customer_name}},\n\nYour support ticket #{{track_id}} has been closed.\nSubject: {{ticket_subject}}\nView: {{ticket_url}}",
      ),
      'ticket_resolved:customer' => $this->template(
        'Customer — Ticket Resolved',
        'ticket_resolved',
        NotificationRecipientType::CUSTOMER,
        'Ticket #{{track_id}} has been resolved',
        '<p>Hello {{customer_name}},</p><p>Your support ticket <strong>#{{track_id}}</strong> has been marked as resolved.</p><p><strong>Subject:</strong> {{ticket_subject}}</p><p>If you still need help, reopen the ticket from your support portal.</p><p><a href="{{ticket_url}}">View ticket</a></p>',
        "Hello {{customer_name}},\n\nYour support ticket #{{track_id}} has been marked as resolved.\nSubject: {{ticket_subject}}\n\nIf you still need help, reopen the ticket from your support portal.\nView: {{ticket_url}}",
      ),
      'ticket_reopened:customer' => $this->template(
        'Customer — Ticket Reopened',
        'ticket_reopened',
        NotificationRecipientType::CUSTOMER,
        'Ticket #{{track_id}} has been reopened',
        '<p>Hello {{customer_name}},</p><p>Your support ticket <strong>#{{track_id}}</strong> has been reopened.</p><p><strong>Subject:</strong> {{ticket_subject}}</p><p><a href="{{ticket_url}}">View ticket</a></p>',
        "Hello {{customer_name}},\n\nYour support ticket #{{track_id}} has been reopened.\nSubject: {{ticket_subject}}\nView: {{ticket_url}}",
      ),
      'ticket_assigned:agent' => $this->template(
        'Agent — Ticket Assigned',
        'ticket_assigned',
        NotificationRecipientType::AGENT,
        'Ticket #{{track_id}} has been assigned to you',
        '<p>Hello {{agent_name}},</p><p>Support ticket <strong>#{{track_id}}</strong> has been assigned to you.</p><p><strong>Subject:</strong> {{ticket_subject}}<br><strong>Priority:</strong> {{ticket_priority}}</p><p><a href="{{ticket_url}}">View ticket</a></p>',
        "Hello {{agent_name}},\n\nSupport ticket #{{track_id}} has been assigned to you.\nSubject: {{ticket_subject}}\nPriority: {{ticket_priority}}\nView: {{ticket_url}}",
      ),
      'ticket_reassigned:agent' => $this->template(
        'Agent — Ticket Reassigned',
        'ticket_reassigned',
        NotificationRecipientType::AGENT,
        'Ticket #{{track_id}} has been reassigned to you',
        '<p>Hello {{agent_name}},</p><p>Support ticket <strong>#{{track_id}}</strong> has been reassigned to you.</p><p><strong>Subject:</strong> {{ticket_subject}}<br><strong>Priority:</strong> {{ticket_priority}}</p><p><a href="{{ticket_url}}">View ticket</a></p>',
        "Hello {{agent_name}},\n\nSupport ticket #{{track_id}} has been reassigned to you.\nSubject: {{ticket_subject}}\nPriority: {{ticket_priority}}\nView: {{ticket_url}}",
      ),
    ];
  }

  /** @return array<string, string> */
  private function template(
    string $name,
    string $event,
    NotificationRecipientType $recipientType,
    string $subject,
    string $htmlContent,
    string $plainTextContent,
  ): array {
    return [
      'name' => $name,
      'event' => $event,
      'recipient_type' => $recipientType->value,
      'status' => NotificationTemplateStatus::ACTIVE->value,
      'subject' => $subject,
      'html_content' => $htmlContent,
      'plain_text_content' => $plainTextContent,
    ];
  }
}
