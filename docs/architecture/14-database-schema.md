# SupportBay – Database Schema Specification (v1)

## Document Information

| Property     | Value                 |
| ------------ | --------------------- |
| Document     | 14-database-schema.md |
| Product      | SupportBay            |
| Version      | v1                    |
| Status       | Approved              |
| Last Updated | June 2026             |

---

# Purpose

This document defines the production database schema for SupportBay.

The schema is designed for scalability, performance, and long-term maintainability while remaining fully compatible with WordPress.

---

# Design Principles

- Dedicated plugin tables.
- WordPress manages users and media.
- BIGINT UNSIGNED IDs.
- UTF8MB4 character set.
- Logical relationships (no DB foreign keys).
- Repository-driven data access.
- Migration-friendly schema.

---

# Table Prefix

All SupportBay tables use the WordPress table prefix.

Example:

```text
wp_sbay_tickets
```

---

# Database Tables

## Core Tables

| Table                          | Purpose                                         |
| ------------------------------ | ----------------------------------------------- |
| wp_sbay_tickets                | Ticket records                                  |
| wp_sbay_messages               | Conversation messages                           |
| wp_sbay_departments            | Support departments                             |
| wp_sbay_purchase_verifications | Verified purchases                              |
| wp_sbay_providers              | Connected provider accounts                     |
| wp_sbay_activities             | Activity timeline                               |
| wp_sbay_magic_tokens           | Passwordless login tokens                       |
| wp_sbay_notification_logs      | Notification delivery logs                      |
| wp_sbay_settings               | Internal plugin settings (optional cache layer) |

---

# WordPress Tables Used

SupportBay intentionally reuses core WordPress tables.

| Table                 | Usage                                               |
| --------------------- | --------------------------------------------------- |
| wp_users              | Customers, Agents, Managers                         |
| wp_usermeta           | User metadata                                       |
| wp_posts              | Not used for tickets                                |
| wp_comments           | Not used                                            |
| wp_options            | Global plugin settings                              |
| wp_posts (attachment) | Optional if WP Media Library integration is enabled |

---

# Table Naming

Rules:

- Singular concepts become plural table names.
- Prefix every table with `sbay_`.
- Snake case only.

Example:

```text
wp_sbay_ticket_messages ❌
wp_sbay_messages ✅
```

---

# Primary Keys

Every table uses:

```sql
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
```

---

# Common Columns

Where applicable:

| Column     | Type     |
| ---------- | -------- |
| created_at | DATETIME |
| updated_at | DATETIME |

Soft-delete should be represented by status fields where required instead of delete flags.

---

# Table Relationships

```text
User
 │
 ├──────────────┐
 │              │
 ▼              ▼
Tickets     Providers
 │
 ▼
Messages
 │
 ▼
Activities

Ticket
 │
 ▼
Purchase Verification
 │
 ▼
Provider
```

---

# Ticket Table

Stores ticket metadata.

Key relationships:

- Customer
- Assigned Agent
- Department
- Purchase Verification

One ticket references one purchase verification.

One purchase verification may be linked to multiple tickets.

---

# Message Table

Stores all public replies and internal notes.

Messages belong to one ticket.

Attachments belong to messages.

---

# Attachment Strategy

SupportBay does **not** maintain a dedicated attachment table in v1.

Attachments are stored using the WordPress uploads directory.

Each message stores attachment metadata (or references to uploaded files) in a structured format.

If future requirements demand advanced attachment management (virus scanning, CDN, lifecycle rules), a dedicated attachment table can be introduced through a migration.

---

# Department Table

Stores:

- Department Name
- Description
- Display Order
- Active Status

Departments are managed from the dashboard.

---

# Purchase Verification Table

Stores normalized purchase information.

Independent of provider.

Fields include:

- Provider
- Purchase Reference
- Product
- License Type
- Support Expiration
- Buyer

Multiple tickets may reference one verification.

---

# Provider Table

Stores customer-provider connections.

Examples:

- Envato OAuth
- WooCommerce
- EDD

Provider credentials must be encrypted before storage.

---

# Activity Table

Stores immutable audit events.

Activities never replace ticket messages.

Activities support:

- Metadata
- Visibility
- Timeline display

---

# Magic Token Table

Stores passwordless authentication tokens.

Tokens are:

- Hashed
- Expiring
- Single-use

Raw token values are never stored.

---

# Notification Log Table

Stores delivery history.

Fields include:

- Event
- Recipient
- Status
- Sent At
- Failure Reason

Notification templates are stored in WordPress options.

---

# Indexing Strategy

Indexes should exist on all frequently queried fields.

Examples:

Tickets

- customer_id
- assigned_to
- department_id
- status
- priority
- track_id
- created_at

Messages

- ticket_id
- created_at

Activities

- ticket_id
- created_at

Purchase Verifications

- provider
- purchase_reference

Magic Tokens

- token_hash
- expires_at
- status

Notification Logs

- recipient_id
- event
- status

---

# Unique Constraints

Examples:

Tickets

track_id

Purchase Verification

provider + purchase_reference

Magic Tokens

token_hash

Providers

provider + provider_user_id

---

# Data Types

Identifiers

BIGINT UNSIGNED

Status

VARCHAR(30)

Names

VARCHAR(191)

Email

VARCHAR(191)

URLs

TEXT

JSON Metadata

LONGTEXT (JSON encoded)

Descriptions

LONGTEXT

Dates

DATETIME

Boolean

TINYINT(1)

---

# JSON Usage

JSON should be limited to flexible metadata.

Examples:

- Provider payloads
- Activity metadata
- Attachment metadata
- Notification payloads

Core relational data should always use dedicated columns.

---

# Database Versioning

SupportBay maintains:

Schema Version

Plugin Version

Schema migrations are independent of plugin releases.

---

# Migration Strategy

Every schema change must be incremental.

Rules:

- Never drop user data automatically.
- Use migration classes.
- Support upgrades from previous versions.
- Log migration results.

---

# Backup Considerations

Before major schema migrations:

- Recommend database backup.
- Provide migration logging.
- Roll forward rather than roll back where possible.

---

# Performance Guidelines

- Use indexes appropriately.
- Avoid unnecessary joins.
- Batch updates where practical.
- Cache expensive provider lookups.
- Paginate large result sets.
- Never use SELECT \* in repositories.

---

# Security

- Escape SQL using `$wpdb->prepare()`.
- Sanitize all inputs.
- Encrypt sensitive provider credentials.
- Hash authentication tokens.
- Validate ownership before exposing records.

---

# Future Schema Extensions

Potential future tables:

- AI conversations
- Live chat sessions
- Webhooks
- SLA policies
- Automation rules
- Knowledge base
- Customer satisfaction ratings
- Saved replies (macros)

These additions should integrate without modifying existing core tables whenever possible.

---

# Approved Decisions

✓ Dedicated SupportBay tables.

✓ WordPress users reused.

✓ Repository-based data access.

✓ Logical relationships.

✓ UTF8MB4.

✓ BIGINT UNSIGNED IDs.

✓ Migration-first approach.

✓ Indexed search fields.

✓ Attachment metadata associated with messages.

✓ Provider-independent purchase verification.

✓ Passwordless authentication table.

✓ Notification delivery logging.

✓ Future-proof schema design.

---
