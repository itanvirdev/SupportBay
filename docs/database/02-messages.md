# SupportBay – Database Specification

## Table: `wp_sbay_messages`

### Purpose

Stores every communication record within a ticket.

Messages represent conversations between customers, support staff, and the system.

Messages do not contain ticket metadata.

Attachments belong to messages.

---

# Relationships

```text
Ticket
 │
 ▼
Messages
 │
 ├── Attachments
 ├── Notifications
 └── Activities
```

## Development Strategy

```
Modules/
└── Messages/
    ├── Database/
    │   └── MessageSchema.php
    │
    ├── Entities/
    │   └── Message.php
    │
    ├── Enums/
    │   └── MessageType.php
    │
    ├── Repositories/
    │   └── MessageRepository.php
    │
    ├── Services/
    │   └── MessageService.php
    │
    └── MessageServiceProvider.php
```

---

# Table Structure

| Column           | Type            | Null | Default           | Index             | Description                             |
| ---------------- | --------------- | ---- | ----------------- | ----------------- | --------------------------------------- |
| id               | BIGINT UNSIGNED | No   | AUTO_INCREMENT    | PRIMARY           | Internal message ID                     |
| ticket_id        | BIGINT UNSIGNED | No   | -                 | INDEX             | Parent ticket                           |
| author_id        | BIGINT UNSIGNED | Yes  | NULL              | INDEX             | WordPress user ID                       |
| author_type      | VARCHAR(20)     | No   | customer          | INDEX             | customer, guest, agent, manager, system |
| type             | VARCHAR(20)     | No   | reply             | INDEX             | reply, internal_note, system            |
| content          | LONGTEXT        | No   | -                 | FULLTEXT (future) | Sanitized HTML content                  |
| edited_by_id     | BIGINT UNSIGNED | Yes  | NULL              | -                 | User who last edited                    |
| edited_at        | DATETIME        | Yes  | NULL              | INDEX             | Last edit timestamp                     |
| customer_read_at | DATETIME        | Yes  | NULL              | INDEX             | Customer viewed message                 |
| staff_read_at    | DATETIME        | Yes  | NULL              | INDEX             | Staff viewed message                    |
| metadata         | LONGTEXT        | Yes  | NULL              | -                 | JSON metadata                           |
| created_at       | DATETIME        | No   | CURRENT_TIMESTAMP | INDEX             | Created date                            |
| updated_at       | DATETIME        | No   | CURRENT_TIMESTAMP | INDEX             | Updated date                            |
| is_deleted       | TINYINT(1)      | No   | DEFAULT 0         |                   |                                         |

---

# Author Types

Supported values:

```text
customer

agent

manager

system
```

---

# Message Types

Supported values:

```text
reply

internal_note

system
```

---

# Content

Stores sanitized HTML.

Supported formatting:

- Bold
- Italic
- Underline
- Bullet List
- Number List
- Alignment
- Links
- Text Color
- Clear Formatting

Toolbar availability is configurable in SupportBay settings.

---

# Edit Tracking

Messages may be edited by staff.

Track:

- Editor
- Edit Timestamp

Future versions may include revision history.

---

# Read Tracking

Customer

```text
customer_read_at
```

Staff

```text
staff_read_at
```

Used for:

- Unread indicators
- Notification suppression
- Dashboard badges

---

# Attachments

Messages may contain zero or more attachments.

Relationship:

```text
Message

↓

Attachments
```

Attachment metadata is stored in the dedicated attachment table.

---

# Internal Notes

Internal Notes:

- Not visible to customers.
- Included in staff timeline.
- Not sent via customer notifications.

---

# System Messages

Automatically generated messages.

Examples:

- Purchase verified
- Ticket merged (future)
- Ticket imported
- AI generated summary (future)

System messages are read-only.

---

# Search

Searchable fields:

- Content
- Author
- Ticket
- Created Date

Future versions may add FULLTEXT indexing where supported.

---

# Metadata

Reserved for flexible data.

Examples:

- Imported message IDs
- Email headers
- AI references
- External provider metadata

Core relational data must remain in dedicated columns.

---

# Business Rules

- Every message belongs to one ticket.
- Every message has one author.
- Every message has one type.
- Messages may have many attachments.
- Customers never see internal notes.
- System messages are read-only.
- Rich text is sanitized before storage.

---

# Index Recommendations

Primary Index

- id

Secondary Indexes

- ticket_id
- author_id
- author_type
- type
- edited_at
- customer_read_at
- staff_read_at
- created_at

---

# Repository Responsibilities

The Message Repository is responsible for:

- Creating messages
- Retrieving conversations
- Editing messages
- Read tracking
- Searching messages

Business validation belongs to the Message Service.

---

# Future Extensions

Potential additions:

- Message reactions
- Emoji support
- AI-generated drafts
- Translation
- Voice messages
- Email threading
- Live chat synchronization
- Revision history

These features should integrate without altering the core message structure.

---

# Approved Decisions

✓ Dedicated messages table.

✓ Unified author model.

✓ Message types.

✓ Staff-only internal notes.

✓ System messages.

✓ Sanitized HTML content.

✓ Attachment relationship.

✓ Read tracking.

✓ Edit tracking.

✓ Repository-driven access.

---
