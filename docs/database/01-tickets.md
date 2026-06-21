# SupportBay – Database Specification

## Table: `wp_sbay_tickets`

### Purpose

Stores the master record for every support ticket created in SupportBay.

One ticket represents one support request.

A ticket contains metadata only.

Conversations are stored in the **Messages** table.

Attachments belong to **Messages**.

Purchase information belongs to **Purchase Verifications**.

Activities belong to **Activities**.

---

# Relationships

```text
Customer (WP User)
        │
        ▼
Ticket
 ├── Department
 ├── Purchase Verification
 ├── Assigned Agent
 ├── Messages
 ├── Activities
 └── Notifications
```

## Development Strategy

```
Modules/
└── Tickets/
    ├── Database/
    │   └── TicketSchema.php
    ├── Entities/
    │   └── Ticket.php
    ├── Enums/
    │   ├── TicketPriority.php
    │   ├── TicketState.php
    │   └── TicketStatus.php
    ├── Http/
    │   └── Controllers/
    │           └── TicketController.php
    ├── Repositories/
    │   └── TicketRepository.php
    ├── Services/
    │   └── TicketService.php
    └── TicketServiceProvider.php
```

---

# Table Structure

| Column                   | Type                 | Null | Default           | Index   | Description                                 |
| ------------------------ | -------------------- | ---- | ----------------- | ------- | ------------------------------------------- |
| id                       | BIGINT UNSIGNED      | No   | AUTO_INCREMENT    | PRIMARY | Internal ticket ID                          |
| track_id                 | CHAR(9)              | No   | -                 | UNIQUE  | Public ticket identifier (e.g. `#54E5DF43`) |
| customer_id              | BIGINT UNSIGNED      | No   | -                 | INDEX   | WordPress customer ID                       |
| created_by_id            | BIGINT UNSIGNED      | Yes  | NULL              | INDEX   | Id (e.g `12`)                               |
| created_by_type          | VARCHAR(20) UNSIGNED | Yes  | NULL              | INDEX   | Customer, Guest, Agent, Manger, System      |
| purchase_verification_id | BIGINT UNSIGNED      | Yes  | NULL              | INDEX   | Purchase verification reference             |
| department_id            | BIGINT UNSIGNED      | No   | General Support   | INDEX   | Support department                          |
| assigned_agent_id        | BIGINT UNSIGNED      | Yes  | NULL              | INDEX   | Assigned support agent                      |
| subject                  | VARCHAR(255)         | No   | -                 | INDEX   | Ticket subject                              |
| status                   | VARCHAR(20)          | No   | open              | INDEX   | Open, Resolved, Closed                      |
| state                    | VARCHAR(20)          | No   | active            | INDEX   | Active, Inactive, Trash                     |
| priority                 | VARCHAR(20)          | No   | normal            | INDEX   | Controlled by staff                         |
| source                   | VARCHAR(30)          | No   | web               | INDEX   | Web, API, Email, live_chat, import (future) |
| last_message_id          | VARCHAR(20)          | Yes  | NULL              | INDEX   | customer, agent, manager, system            |
| last_reply_at            | DATETIME             | Yes  | NULL              | INDEX   | Last reply timestamp                        |
| first_response_at        | DATETIME             | Yes  | NULL              | -       | First staff response                        |
| resolved_at              | DATETIME             | Yes  | NULL              | -       | Resolution timestamp                        |
| closed_at                | DATETIME             | Yes  | NULL              | -       | Closure timestamp                           |
| reopened_at              | DATETIME             | Yes  | NULL              | -       | Reopen timestamp                            |
| is_public                |                      |      |                   |         |                                             |
| public_token             |                      |      |                   |         |                                             |
| metadata                 | LONGTEXT             | Yes  | NULL              | -       | JSON metadata                               |
| created_at               | DATETIME             | No   | CURRENT_TIMESTAMP | INDEX   | Created date                                |
| updated_at               | DATETIME             | No   | CURRENT_TIMESTAMP | INDEX   | Updated date                                |

---

# Workflow Status

Supported values:

```text
open

resolved

closed
```

---

# Record State

Supported values:

```text
active

inactive

trash
```

---

# Priority

Supported values:

```text
normal

medium

high

urgent
```

Default:

```
normal
```

Only Support Agents, Support Managers, and Administrators can change ticket priority.

Customers cannot set or modify priority.

---

# Ticket Source

Supported values (v1):

```text
web
```

Reserved for future:

```text
api

email

live_chat

import
```

---

# Track ID

Public identifier.

Example:

```
#54E5DF43
```

Rules:

- Generated automatically.
- Immutable.
- Unique.
- Never reused.

Displayed in:

- Customer Portal
- Emails
- Notifications
- Dashboard
- Search

---

# Assignment

A ticket may remain unassigned.

Workflow:

```
New Ticket

↓

Unassigned Queue

↓

Manager Assignment

or

Agent Claims Ticket
```

---

# Purchase Verification

Relationship:

```
One Ticket

↓

One Verification
```

Multiple tickets may reference the same verification.

---

# Department

Required.

Default:

```
General Support
```

Administrators may create additional departments.

---

# Reopening Rules

Customers may reopen a resolved ticket within **30 days**.

After 30 days:

- Reopening is blocked.
- Customer is prompted to create a new ticket.

Closed tickets cannot be reopened.

---

# Searchable Fields

Optimized for searching:

- Track ID
- Subject
- Customer
- Department
- Status
- Assigned Agent

Message content is searched through the Messages table.

---

# Metadata Usage

Reserved for non-relational data.

Examples:

- Imported ticket flags
- Migration references
- Future integrations

Core business data must use dedicated columns.

---

# Business Rules

- One ticket belongs to one customer.
- One ticket belongs to one department.
- One ticket references zero or one purchase verification.
- One ticket may have many messages.
- One ticket may have many activities.
- One ticket may have many notification log entries.
- One ticket may remain unassigned.
- Priority is managed by staff only.
- Track ID never changes.

---

# Index Recommendations

Primary Index:

- id

Unique Index:

- track_id

Secondary Indexes:

- customer_id
- assigned_agent_id
- department_id
- purchase_verification_id
- workflow_status
- record_state
- priority
- subject
- last_reply_at
- created_at

---

# Repository Responsibilities

The Ticket Repository is responsible for:

- Creating tickets
- Updating ticket metadata
- Assigning agents
- Updating workflow status
- Updating record state
- Managing priority
- Retrieving tickets
- Searching tickets

Business rules remain in the Ticket Service.

---

# Future Extensions

Potential additions:

- SLA tracking
- Due dates
- Estimated response times
- Customer satisfaction rating
- AI summary
- Automation labels
- Custom fields

These should be introduced through future migrations without breaking existing data.

---

## Modules Tickets

```
Modules/
└── Tickets/
    ├── Database/
    ├── Entities/
    │   └── Ticket.php
    ├── Enums/
    ├── Http/
    │   └── Controllers/
    ├── Repositories/
    ├── Services/
    └── TicketServiceProvider.php
```

---

# Approved Decisions

✓ Dedicated ticket table.

✓ Internal numeric ID.

✓ Public Track ID.

✓ One ticket = one customer.

✓ One ticket = one purchase verification.

✓ Multiple tickets may reuse the same verification.

✓ Department required.

✓ Staff-managed priority.

✓ Separate workflow status and record state.

✓ Unassigned queue supported.

✓ Customer reopen window of 30 days.

✓ Repository-driven data access.

✓ Public sharing ticket.

---
