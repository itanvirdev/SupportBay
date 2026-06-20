# SupportBay – Attachment System Specification (v1)

## Document Information

| Property     | Value             |
| ------------ | ----------------- |
| Document     | 04-attachments.md |
| Product      | SupportBay        |
| Version      | v1                |
| Status       | Approved          |
| Last Updated | June 2026         |

---

# Purpose

The Attachment System allows customers, agents, and managers to securely upload, preview, and download files associated with ticket conversations.

SupportBay treats attachments as part of a conversation, ensuring complete auditability and context.

Attachments belong to **messages**, not directly to tickets.

---

# Architecture Principles

## Secure by Default

Attachments must never be directly accessible using their physical file path.

All downloads must pass through SupportBay's permission system.

---

## Conversation-Centric

Attachments belong to messages.

Relationship:

Ticket  
↓  
Messages  
↓  
Attachments

This ensures files always have conversational context.

---

## Administrator Controlled

Attachment behavior must be configurable through the SupportBay dashboard.

Administrators can:

- Enable or disable uploads
- Define upload limits
- Configure allowed file types
- Configure previews
- Control attachment-related security settings

---

# Dashboard Settings

## General Settings

### Enable File Uploads

Type: Boolean

Default: Enabled

Description: Allows administrators to globally enable or disable file uploads.

---

### Maximum Files Per Message

Type: Integer

Default: 5

Description: Defines how many files can be uploaded with a single message.

---

### Maximum File Size

Type: Integer (MB)

Default: 10 MB

Description: Defines the maximum allowed size for each uploaded file.

---

# Allowed File Categories

Administrators can enable or disable categories individually.

---

## Photos

Default: Enabled

Extensions:

- jpg
- jpeg
- png
- webp
- gif

Features:

- Thumbnail generation
- Popup preview support

---

## Videos

Default: Disabled

Extensions:

- mp4
- webm
- mov
- avi
- ogv

Notes: Video uploads may consume significant storage space.

---

## Audios

Default: Disabled

Extensions:

- mp3
- wav
- aac
- ogg
- flac
- m4a
- wma

---

## Documents

Default: Enabled

Extensions:

- doc
- docx
- xls
- xlsx

---

## Text Files

Default: Enabled

Extensions:

- txt

---

## CSV Files

Default: Enabled

Extensions:

- csv

---

## PDF Files

Default: Enabled

Extensions:

- pdf

Features:

- Inline preview support

---

## ZIP Archives

Default: Enabled

Extensions:

- zip

---

## JSON Files

Default: Disabled

Extensions:

- json

---

## 3D Models

Default: Disabled

Extensions:

- stl

Use Cases:

- 3D printing products
- CAD products
- Design assets

---

## Medical Images

Default: Disabled

Extensions:

- dcm

Use Cases:

- Medical consultation
- Healthcare environments

---

# Preview Settings

## Enable Image Popup Preview

Type: Boolean

Default: Enabled

Description: Displays image attachments inside a modal popup without downloading.

Supported Types:

- jpg
- jpeg
- png
- webp
- gif

---

## Enable PDF Preview

Type: Boolean

Default: Enabled

Description: Allows users to preview PDF files directly within the portal.

---

# Attachment Permissions

## Customer

May access attachments when:

- The customer owns the ticket.
- The attachment belongs to a customer-visible message.

---

## Agent

May access attachments when:

- The agent has access to the ticket.

By default: All agents can view ticket attachments.

---

## Manager

Always has access.

---

## Administrator

Always has access.

---

# Internal Notes Attachments

Attachments uploaded through internal notes follow internal note visibility settings.

Visibility Options:

- Managers Only
- Managers and Agents

Customers can never access internal note attachments.

---

# Secure Storage Strategy

## Physical Storage

Files are stored outside the public media workflow.

Example:

wp-content/uploads/supportbay/YYYY/MM/

---

## Public Access

Direct file access is prohibited.

Invalid Example:

/wp-content/uploads/supportbay/file.pdf

---

## Secure Downloads

Downloads must be processed through SupportBay.

Example:

/wp-json/supportbay/v1/attachments/{id}/download

---

# Download Authorization Flow

User Requests Download  
↓  
Load Attachment  
↓  
Load Parent Message  
↓  
Load Parent Ticket  
↓  
Validate Permissions  
↓  
Serve File

Unauthorized Requests: Return HTTP 403.

---

# Attachment Database Relationship

Ticket  
↓  
Messages  
↓  
Attachments

One Message  
↓  
Many Attachments

One Attachment  
↓  
One Message

---

# Attachment Metadata

Each attachment record stores:

- Attachment ID
- Parent Message ID
- Uploaded By
- Original Filename
- Stored Filename
- MIME Type
- Extension
- File Size
- Storage Path
- Checksum (SHA256)
- Upload Timestamp

---

# Security Rules

The following validations must always be enforced:

- File extension validation
- MIME type validation
- File size validation
- Permission validation
- Secure download authorization

---

# Restricted File Types

The following file types are permanently blocked and cannot be enabled:

- php
- php3
- php4
- php5
- phtml
- exe
- bat
- sh
- js
- jar
- com
- msi
- ps1

Reason: Prevent remote code execution and malicious uploads.

---

# Cleanup Strategy

Attachments associated with tickets moved to Trash should remain available for a retention period.

Default Retention: 30 days

After retention expires:

- Attachment records are deleted.
- Physical files are permanently removed.

---

# Future Enhancements

Potential future improvements include:

- Cloud storage integration
- Amazon S3 support
- Google Cloud Storage support
- Dropbox integration
- Attachment versioning
- Virus scanning integrations
- Drag-and-drop uploads
- Chunked uploads for large files
- Video preview support
- 3D model preview support

---

# Developer Hooks

Before Upload:

supportbay_before_attachment_upload

After Upload:

supportbay_attachment_uploaded

Before Download:

supportbay_before_attachment_download

After Download:

supportbay_attachment_downloaded

Before Deletion:

supportbay_before_attachment_delete

After Deletion:

supportbay_attachment_deleted

---

# Approved Decisions

✓ Attachments belong to messages.

✓ Secure downloads only.

✓ Dashboard-configurable upload settings.

✓ Image popup previews enabled.

✓ PDF previews supported.

✓ All agents can access customer-visible attachments.

✓ Internal note attachments respect visibility settings.

✓ Executable file types are permanently blocked.

✓ Attachments are stored outside the WordPress Media Library workflow.

---
