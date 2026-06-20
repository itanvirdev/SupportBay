# SupportBay – Magic Login System Specification (v1)

## Document Information

| Property     | Value             |
| ------------ | ----------------- |
| Document     | 10-magic-links.md |
| Product      | SupportBay        |
| Version      | v1                |
| Status       | Approved          |
| Last Updated | June 2026         |

---

# Purpose

The Magic Login System allows customers to securely access their SupportBay account and tickets without manually entering a password.

This feature is primarily designed for:

- Guest ticket submissions
- Pre-sales inquiries
- Passwordless customer onboarding
- Quick ticket access from email notifications

---

# Core Principles

## Password Optional

A customer can interact with SupportBay without immediately creating a password.

---

## Secure Access

All magic links must be:

- Single use
- Time limited
- Cryptographically secure

---

## Customer Friendly

Users should never need to:

- Remember credentials
- Request password resets
- Create passwords during ticket submission

---

# Primary Use Cases

## Guest Ticket Submission

Customer submits ticket.

↓

SupportBay creates customer account.

↓

Generates Magic Login Link.

↓

Emails link to customer.

↓

Customer clicks link.

↓

Automatically logged in.

↓

Redirected to ticket.

---

## Email Notifications

Customer receives:

New Reply Notification

↓

Clicks:

View Ticket

↓

Automatically authenticated using magic token.

↓

Redirected to ticket.

---

## Account Access

Customer visits:

Support Portal

↓

Clicks:

Email Me Login Link

↓

Receives secure access link.

↓

Logged in automatically.

---

# Guest Ticket Flow

Guest Visitor
↓
Submit Ticket
↓
Account Created
↓
Role: Customer
↓
Magic Link Generated
↓
Email Sent
↓
Customer Accesses Ticket

---

# Account Creation

Upon guest ticket submission:

SupportBay automatically creates:

WordPress User

Role:

sbay_customer

Status:

Active

Password:

Randomly generated

Customer does not need to know the password.

---

# Magic Link Structure

Example:

```text id="2w5w6s"
https://example.com/supportbay/magic-login?token=xxxxx
```

Alternative:

```text id="gg2k6i"
https://example.com/supportbay/login/xxxxx
```

Implementation detail may vary.

---

# Token Requirements

Magic tokens must:

- Be unique
- Be random
- Be non-guessable
- Be hashed before storage

Recommended:

256-bit secure token

---

# Token Lifetime

Default:

24 Hours

Configurable by administrator.

Recommended Range:

- Minimum: 15 minutes
- Maximum: 7 days

---

# Single Use Policy

After successful login:

Token becomes invalid.

Cannot be reused.

---

# Token States

active

used

expired

revoked

---

# Authentication Flow

Customer Clicks Link
↓
Validate Token
↓
Check Expiry
↓
Check Status
↓
Authenticate User
↓
Mark Token Used
↓
Redirect

---

# Redirect Behavior

## Ticket Access

Redirect:

Specific Ticket

Example:

/ticket/1646

---

## Portal Login

Redirect:

Customer Dashboard

---

# Customer Dashboard Access

Once authenticated:

Customer remains logged in using WordPress authentication cookies.

No additional login required.

---

# Password Management

After login:

Customer may:

- Set password
- Change password
- Continue passwordless usage

---

# Security Rules

## Expired Tokens

Display:

This login link has expired.

Request a new login link.

---

## Used Tokens

Display:

This login link has already been used.

Request a new login link.

---

## Invalid Tokens

Display:

Invalid login link.

---

## Rate Limiting

Protect login link generation.

Recommended:

5 requests per hour per email address

---

# Dashboard Settings

SupportBay
↓
Settings
↓
Authentication

---

# Authentication Settings

## Enable Magic Login

Default:

Enabled

---

## Token Lifetime

Default:

24 Hours

---

## Single Use Tokens

Default:

Enabled

---

## Allow Password Login

Default:

Enabled

---

## Auto Login After Ticket Creation

Default:

Enabled

---

# Email Template

Template:

Customer Magic Login

Event:

magic_login

Recipient:

Customer

---

## Example Content

Subject:

Access Your SupportBay Account

Content:

Hello {customer_name},

Click the secure link below to access your support account:

{magic_login_url}

This link will expire in {expiration_time}.

---

# Activity Logs

Generate activities:

magic_link_generated

magic_link_used

magic_link_expired

magic_link_revoked

---

# Database Table

Table:

wp_sbay_magic_tokens

---

## Columns

| Column       | Type            | Nullable | Description             |
| ------------ | --------------- | -------- | ----------------------- |
| id           | BIGINT UNSIGNED | No       | Token ID                |
| user_id      | BIGINT UNSIGNED | No       | Customer ID             |
| token_hash   | VARCHAR(255)    | No       | Secure token hash       |
| purpose      | VARCHAR(50)     | No       | Login purpose           |
| redirect_url | TEXT            | Yes      | Destination after login |
| status       | VARCHAR(20)     | No       | Token state             |
| expires_at   | DATETIME        | No       | Expiration time         |
| used_at      | DATETIME        | Yes      | Usage timestamp         |
| created_at   | DATETIME        | No       | Creation timestamp      |

---

# Token Purging

Expired tokens should be cleaned automatically.

Suggested Schedule:

Daily

---

# Permissions

Customer

Can:

- Request login links
- Use login links

Cannot:

- Generate links for other users

---

Agent

No access.

---

Manager

No access.

---

Administrator

Can:

- Configure authentication settings
- Revoke tokens
- View token logs

Cannot:

- View raw token values

---

# Future Enhancements

Potential v2 Features

- Device recognition
- Trusted devices
- Login history
- Multi-factor authentication
- Passkeys
- Passwordless-only mode
- Team login links

---

# Approved Decisions

✓ Guest ticket submission supported.

✓ Automatic customer account creation.

✓ Magic login links enabled.

✓ Single-use tokens.

✓ Expiring tokens.

✓ Secure token hashing.

✓ Auto-login after ticket creation.

✓ Customer can later set a password.

✓ Magic link activity logging.

✓ Dedicated token table.

---
