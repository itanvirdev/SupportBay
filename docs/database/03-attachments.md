# SupportBay – Database Specification

## Table: `wp_sbay_attachments`

---

# Purpose

Stores metadata for all files uploaded within SupportBay.

Attachments belong to messages and inherit their visibility rules.

Files are stored in a configured storage system (local or cloud).

---

# Relationships

```text id="t1"
Ticket
  ↓
Message
  ↓
Attachment
```

---

# Table Structure

| Column           | Type            | Null | Default           | Index   | Description                                 |
| ---------------- | --------------- | ---- | ----------------- | ------- | ------------------------------------------- |
| id               | BIGINT UNSIGNED | No   | AUTO_INCREMENT    | PRIMARY | Attachment ID                               |
| message_id       | BIGINT UNSIGNED | No   | -                 | INDEX   | Parent message                              |
| ticket_id        | BIGINT UNSIGNED | No   | -                 | INDEX   | Denormalized for performance                |
| uploaded_by      | BIGINT UNSIGNED | Yes  | NULL              | INDEX   | WP user ID                                  |
| disk             | VARCHAR(50)     | No   | local             | INDEX   | Storage driver (local, s3, r2)              |
| original_name    | VARCHAR(255)    | No   | -                 | -       | Original filename                           |
| stored_name      | VARCHAR(255)    | No   | -                 | UNIQUE  | Generated secure filename                   |
| path             | TEXT            | No   | -                 | -       | Relative storage path                       |
| file_size        | BIGINT UNSIGNED | No   | 0                 | INDEX   | File size in bytes                          |
| extension        | VARCHAR(20)     | No   | -                 | INDEX   | File extension                              |
| mime_type        | VARCHAR(150)    | No   | -                 | INDEX   | MIME type                                   |
| category         | VARCHAR(30)     | No   | document          | INDEX   | image, video, audio, document, archive, etc |
| checksum         | CHAR(64)        | Yes  | NULL              | INDEX   | SHA-256 hash                                |
| width            | INT UNSIGNED    | Yes  | NULL              | -       | Image width                                 |
| height           | INT UNSIGNED    | Yes  | NULL              | -       | Image height                                |
| duration         | DECIMAL(10,2)   | Yes  | NULL              | -       | Media duration (sec)                        |
| is_previewable   | TINYINT(1)      | No   | 0                 | INDEX   | Can be previewed in UI                      |
| scan_status      | VARCHAR(20)     | No   | pending           | INDEX   | pending, clean, infected, failed            |
| state            | VARCHAR(20)     | No   | active            | INDEX   | active, deleted, quarantined                |
| download_count   | INT UNSIGNED    | No   | 0                 | -       | Analytics counter                           |
| last_accessed_at | DATETIME        | Yes  | NULL              | INDEX   | Last download time                          |
| metadata         | LONGTEXT        | Yes  | NULL              | -       | JSON extra data                             |
| created_at       | DATETIME        | No   | CURRENT_TIMESTAMP | INDEX   | Upload time                                 |
| updated_at       | DATETIME        | No   | CURRENT_TIMESTAMP | INDEX   | Update time                                 |

---

# Storage Disk

Supported (v1):

```text id="t2"
local
```

Future:

```text id="t3"
s3
cloudflare_r2
digitalocean_spaces
bunny_storage
```

---

# File Categories

Auto-detected:

```text id="t4"
image
video
audio
document
archive
pdf
csv
json
medical
3d
other
```

---

# State

```text id="t5"
active
deleted
quarantined
```

---

# Scan Status

```text id="t6"
pending
clean
infected
failed
```

---

# Business Rules

## 1. Attachment ownership

- Every attachment belongs to a message
- Every message belongs to a ticket

---

## 2. Access control

Attachment access is determined by:

- Ticket ownership
- Message type
- Ticket public status

Rules:

- `internal_note` attachments → never public
- `reply` attachments → follow ticket visibility
- `system` attachments → staff only by default

---

## 3. Denormalization

We store:

```text id="t7"
ticket_id
```

even though message already has it.

Reason:

- faster queries
- file listing by ticket
- admin dashboard performance

---

## 4. Upload validation

Controlled via admin settings:

- max file size
- allowed mime types
- allowed extensions
- enable/disable uploads

---

## 5. Security rules

- No direct file access
- Random stored filenames
- MIME + extension validation
- checksum verification
- quarantine support
- sanitize original filenames

---

## 6. Download system

All downloads must pass through:

```text id="t8"
/supportbay/download/{attachment_id}
```

Checks:

- permissions
- ticket access
- message type visibility
- token validation (future)

---

# Index Strategy

- PRIMARY: id
- INDEX: message_id
- INDEX: ticket_id
- INDEX: uploaded_by
- INDEX: mime_type
- INDEX: category
- INDEX: state
- INDEX: scan_status
- INDEX: created_at
- INDEX: checksum

---

# Repository Responsibilities

Attachment Repository handles:

- upload orchestration
- metadata storage
- file retrieval
- download validation
- deletion/quarantine
- storage abstraction

Business rules handled in service layer.

---

# Future Enhancements

- CDN integration
- expiring signed URLs
- virus scanning (ClamAV / cloud APIs)
- OCR extraction
- AI image analysis
- thumbnail generation pipeline
- file compression optimization
- duplicate detection via checksum
- cross-ticket reuse system (optional advanced feature)

---

# Approved Decisions

✓ Dedicated attachment table
✓ Attachments belong to messages
✓ Ticket-level denormalization for performance
✓ Storage abstraction (local + cloud future-ready)
✓ No direct file URLs
✓ Secure download controller required
✓ Lifecycle states (active/quarantined/deleted)
✓ Scan status tracking
✓ Full metadata support
✓ Message-based visibility inheritance

---
