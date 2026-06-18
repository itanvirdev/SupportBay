# SupportBay – Database Specification

## Table: `wp_sbay_notification_logs`

---

# Purpose

Stores a complete log of all notifications sent by SupportBay.

This includes email notifications, and will later support additional channels such as SMS, Slack, WhatsApp, and webhooks.

The notification log acts as a **delivery audit system** for all outbound communications.

---

# Relationships

```text id="n1"
Ticket
   │
   ▼
Notification Logs
```

Optionally:

```text id="n2"
User / System / Provider Events
        │
        ▼
Notification Logs
```

Each log entry may belong to a ticket or be system-wide.

---

# Table Structure

| Column              | Type            | Null | Default           | Index   | Description                                 |
| ------------------- | --------------- | ---- | ----------------- | ------- | ------------------------------------------- |
| id                  | BIGINT UNSIGNED | No   | AUTO_INCREMENT    | PRIMARY | Log ID                                      |
| ticket_id           | BIGINT UNSIGNED | Yes  | NULL              | INDEX   | Related ticket (if applicable)              |
| user_id             | BIGINT UNSIGNED | Yes  | NULL              | INDEX   | Recipient user                              |
| channel             | VARCHAR(30)     | No   | email             | INDEX   | email, sms, slack, webhook                  |
| event               | VARCHAR(50)     | No   | -                 | INDEX   | trigger event (ticket_created, reply, etc.) |
| recipient           | VARCHAR(255)    | No   | -                 | INDEX   | Email/phone/webhook URL                     |
| subject             | VARCHAR(255)    | Yes  | NULL              | -       | Notification subject                        |
| payload             | LONGTEXT        | Yes  | NULL              | -       | JSON data sent                              |
| status              | VARCHAR(20)     | No   | pending           | INDEX   | pending, sent, failed, delivered            |
| provider            | VARCHAR(50)     | Yes  | NULL              | INDEX   | SMTP, SES, SendGrid, etc.                   |
| provider_message_id | VARCHAR(255)    | Yes  | NULL              | INDEX   | External provider reference                 |
| error_message       | TEXT            | Yes  | NULL              | -       | Failure reason                              |
| retry_count         | INT UNSIGNED    | No   | 0                 | INDEX   | Retry attempts                              |
| scheduled_at        | DATETIME        | Yes  | NULL              | INDEX   | Scheduled send time                         |
| sent_at             | DATETIME        | Yes  | NULL              | INDEX   | Actual send time                            |
| delivered_at        | DATETIME        | Yes  | NULL              | INDEX   | Delivery confirmation (if available)        |
| metadata            | LONGTEXT        | Yes  | NULL              | -       | Additional JSON data                        |
| created_at          | DATETIME        | No   | CURRENT_TIMESTAMP | INDEX   | Created time                                |
| updated_at          | DATETIME        | No   | CURRENT_TIMESTAMP | INDEX   | Updated time                                |

---

# Supported Channels

```text id="n3"
email
sms
whatsapp
slack
webhook
push
```

---

# Event Types

```text id="n4"
ticket_created
ticket_reply
ticket_assigned
ticket_closed
ticket_reopened
purchase_verified
auth_login_link
system_alert
```

---

# Status Flow

```text id="n5"
pending
    ↓
sent
    ↓
delivered (optional)
    ↓
failed
```

Retryable:

- pending
- failed

Final states:

- sent
- delivered

---

# Business Rules

- Every notification attempt must be logged.
- Logs are immutable after sending (only status updates allowed).
- Each notification belongs to an event trigger.
- A ticket may have multiple notification logs.
- Failed notifications can be retried.
- Logs are not deleted automatically (audit requirement).

---

# Retry System

SupportBay should support retries:

- exponential backoff (future)
- manual retry by admin
- scheduled retry worker (cron)

---

# Provider Integration

Each channel may have multiple providers:

Example:

Email:

- SMTP
- SendGrid
- Amazon SES

SMS:

- Twilio
- Nexmo

This is handled via `provider` field.

---

# Repository Responsibilities

Notification Log Repository handles:

- creating log entries
- updating status
- tracking delivery
- retry management
- querying history

Actual sending logic belongs to Notification Service.

---

# Index Strategy

Primary Index:

- id

Secondary Indexes:

- ticket_id
- user_id
- channel
- event
- status
- provider
- scheduled_at
- sent_at
- created_at

---

# Future Enhancements

- Real-time delivery tracking
- Webhook retry queue
- Notification batching
- AI-generated notification summaries
- Multi-language notification logs
- SLA-based notification escalation

---

# Approved Decisions

✓ Full notification audit system

✓ Multi-channel support (future-ready)

✓ Retry mechanism support

✓ Provider-aware logging

✓ Event-based notification tracking

✓ Immutable delivery history

✓ Scalable architecture for SMS/Slack/Webhooks

---
