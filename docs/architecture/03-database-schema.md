# SupportBay Database Schema v1

## Part 1 – Tickets

---

# Table: wp_sbay_tickets

## Purpose

Represents a support request submitted by a customer.

This table should NOT store:

- Messages / Replies
- Attachments
- Activity Logs
- Notification Records

These belong to their own tables.

---

# Business Rules

## Priority

Customers cannot select priority.

Default:

- Normal

Managers and Agents may update priority later.

Available values:

- Normal
- Medium
- High

---

## Departments

Department is required.

Default department:

- General Support

Departments are managed by Administrators and Managers.

Customers choose from public departments during ticket creation.

Examples:

- General Support
- Pre-Sales
- Technical Support
- Billing

---

## Assignment

Tickets may remain unassigned.

Workflow:

New Ticket  
↓  
Unassigned Queue  
↓  
Manager Assigns Agent

OR

Agent Replies First  
↓  
Agent Automatically Becomes Owner

---

## Reopening Policy

Customers may reopen resolved tickets within 30 days.

Workflow:

Resolved  
↓  
Customer Reply  
↓  
Ticket Status: Reopened  
↓  
Ticket Status: Awaiting Agent

After 30 days:

Customer Reply Attempt  
↓  
SupportBay Suggests Creating a New Ticket

---

# Columns

| Column                   | Type            | Nullable | Description                                |
| ------------------------ | --------------- | -------- | ------------------------------------------ |
| id                       | BIGINT UNSIGNED | No       | Internal ticket ID                         |
| track_id                 | VARCHAR(20)     | No       | Public ticket identifier                   |
| customer_id              | BIGINT UNSIGNED | No       | WordPress customer user ID                 |
| department_id            | BIGINT UNSIGNED | No       | Assigned department                        |
| assigned_to              | BIGINT UNSIGNED | Yes      | Assigned support agent                     |
| purchase_verification_id | BIGINT UNSIGNED | Yes      | Linked Envato purchase verification        |
| subject                  | VARCHAR(255)    | No       | Ticket subject                             |
| status                   | VARCHAR(30)     | No       | Ticket workflow status                     |
| priority                 | VARCHAR(20)     | No       | Ticket priority                            |
| record_state             | VARCHAR(20)     | No       | Active, Inactive, Trash                    |
| created_by               | BIGINT UNSIGNED | No       | User who created the ticket                |
| resolved_at              | DATETIME        | Yes      | Resolution timestamp                       |
| closed_at                | DATETIME        | Yes      | Closure timestamp                          |
| last_reply_at            | DATETIME        | Yes      | Latest customer or agent reply             |
| created_at               | DATETIME        | No       | Creation timestamp                         |
| updated_at               | DATETIME        | No       | Last update timestamp                      |
| reopened_count           | SMALLINT        |          | Number of times a ticket has been reopened |

---

# Status Values

Customer-facing workflow statuses:

- New
- Open
- Awaiting Customer
- Awaiting Agent
- Resolved
- Reopened
- Closed

---

# Record States

Administrative lifecycle states:

- Active
- Inactive
- Trash

---

# Default Values

status:

- New

priority:

- Normal

record_state:

- Active

assigned_to:

- NULL

resolved_at:

- NULL

closed_at:

- NULL

last_reply_at:

- NULL

---

# Constraints

track_id:

- Must be unique.

Examples:

- 54E5DF43
- B9D2A81F

customer_id:

- Must reference an existing WordPress user.

department_id:

- Must reference an existing department.

assigned_to:

- May be NULL.
- If assigned, must reference an agent-capable WordPress user.

purchase_verification_id:

- Optional.
- Must reference a valid purchase verification record.

---

# Suggested Indexes

PRIMARY KEY:

- id

UNIQUE INDEX:

- track_id

INDEXES:

- customer_id
- assigned_to
- department_id
- purchase_verification_id
- status
- priority
- record_state
- created_at
- last_reply_at

---

# Notes

The track ID is the only identifier exposed publicly.

Examples:

Portal URL: /portal/tickets/54E5DF43

Emails: Track ID: #54E5DF43

The internal numeric ID should never be exposed to customers.

---

## Part 2 – Messages

---

# Table: wp_sbay_messages

## Purpose

Represents a single entry in a ticket conversation.

A message may be:

- A customer reply
- An agent reply
- An internal note
- A system-generated event

Attachments belong to messages.

---

# Business Rules

## Message Ownership

Each message belongs to exactly one ticket.

Relationship:

Ticket  
↓  
Many Messages

---

## Message Author

Each message has one author.

Examples:

Customer Reply:

author_id = Customer User ID

Agent Reply:

author_id = Agent User ID

Internal Note:

author_id = Agent or Manager User ID

System Event:

author_id = NULL

---

## Message Editing

Editing is not allowed.

Once submitted:

- Message content becomes immutable.
- Original records are preserved.

---

## Rich Text Support

Supported formatting:

- Bold
- Italic
- Underline
- Bullet Lists
- Numbered Lists
- Alignment
- Hyperlinks
- Text Color
- Clear Formatting

Unsupported:

- HTML Editing
- Tables
- Iframes
- Video Embeds
- Custom CSS

All content must be sanitized before storage.

---

## Internal Notes Visibility

Configured globally.

Options:

Managers Only

OR

Agents and Managers

Customers can never access internal notes.

---

# Message Types

Customer Reply

Visible to:

- Customer
- Agents
- Managers

---

Agent Reply

Visible to:

- Customer
- Agents
- Managers

---

Internal Note

Visible to:

- Managers
- Agents (depending on settings)

Hidden from customers.

---

System Event

Visible to:

- Agents
- Managers

Hidden from customers.

Examples:

- Ticket assigned.
- Priority changed.
- Department changed.
- Ticket reopened.
- Purchase verification completed.

---

# Columns

| Column       | Type            | Nullable | Description                 |
| ------------ | --------------- | -------- | --------------------------- |
| id           | BIGINT UNSIGNED | No       | Internal message ID         |
| ticket_id    | BIGINT UNSIGNED | No       | Related ticket              |
| author_id    | BIGINT UNSIGNED | Yes      | WordPress user ID           |
| message_type | VARCHAR(30)     | No       | Message category            |
| content      | LONGTEXT        | Yes      | Sanitized rich text content |
| is_internal  | TINYINT(1)      | No       | Internal visibility flag    |
| created_at   | DATETIME        | No       | Creation timestamp          |

---

# Default Values

is_internal:

0

created_at:

Current timestamp

---

# Message Type Values

customer_reply

agent_reply

internal_note

system_event

---

# Visibility Rules

Customer Reply:

Customer → Visible

Agents → Visible

Managers → Visible

---

Agent Reply:

Customer → Visible

Agents → Visible

Managers → Visible

---

Internal Note:

Customer → Hidden

Managers → Visible

Agents → Based on Settings

---

System Event:

Customer → Hidden

Agents → Visible

Managers → Visible

---

# Constraints

ticket_id: Must reference an existing ticket.

author_id: May be NULL only for system events.

customer_reply: author_id must reference the ticket owner.

agent_reply: author_id must reference an agent-capable user.

internal_note: author_id must reference an agent or manager.

system_event: author_id may be NULL.

---

# Suggested Indexes

PRIMARY KEY: id

INDEXES: ticket_id, author_id, message_type, created_at

---

# Timeline Order

Messages are always displayed chronologically.

Oldest First:

Customer creates ticket  
↓  
Agent replies  
↓  
Customer replies  
↓  
Internal note  
↓  
System event

Newest messages appear at the bottom.

---

# Attachments

Attachments do not belong to tickets.

Relationship:

Ticket  
↓  
Messages  
↓  
Attachments

Each attachment references a single message.

---

# Status Automation

Customer Reply:

Ticket Status: Awaiting Agent

Resolved + Customer Reply (within 30 days):

Reopened  
↓  
Awaiting Agent

---

Agent Reply:

Ticket Status: Awaiting Customer

---

System Event: No status change unless explicitly defined.

Examples:

Assignment event  
Priority change event

---

# Notes

The message table is the core communication layer of SupportBay.

All conversations, notes, and system-generated events should pass through this table to maintain a complete and auditable ticket history.

---
