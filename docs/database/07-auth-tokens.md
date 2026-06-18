# SupportBay – Database Specification

## Table: `wp_sbay_auth_tokens`

---

# Purpose

Stores secure authentication tokens used for passwordless login and future authentication-related features.

The primary use case in v1 is **Magic Login**, allowing guest customers to securely access their support tickets without creating a password first.

The authentication system is provider-independent and designed to support additional authentication flows in future releases.

---

# Authentication Flow

```text
Guest
    │
    ▼
Submit Ticket
    │
    ▼
Auto-create Customer Account
    │
    ▼
Generate Auth Token
    │
    ▼
Send Email
    │
    ▼
Customer Clicks Magic Link
    │
    ▼
Validate Token
    │
    ▼
Create WordPress Login Session
    │
    ▼
Redirect to Requested Page
    │
    ▼
(Optional) Customer Sets Password
    │
    ▼
Revoke All Active Magic Login Tokens
```

---

# Relationships

```text
Customer
     │
     ▼
Auth Tokens
```

One customer may have multiple active authentication tokens.

---

# Table Structure

| Column       | Type            | Null | Default           | Index   | Description                                          |
| ------------ | --------------- | ---- | ----------------- | ------- | ---------------------------------------------------- |
| id           | BIGINT UNSIGNED | No   | AUTO_INCREMENT    | PRIMARY | Internal token ID                                    |
| user_id      | BIGINT UNSIGNED | No   | -                 | INDEX   | Related WordPress user                               |
| type         | VARCHAR(30)     | No   | magic_login       | INDEX   | Authentication token type                            |
| token_hash   | CHAR(64)        | No   | -                 | UNIQUE  | SHA-256 hash of the generated token                  |
| redirect_to  | VARCHAR(255)    | Yes  | NULL              | -       | Redirect destination after successful authentication |
| expires_at   | DATETIME        | No   | -                 | INDEX   | Token expiration date/time                           |
| last_used_at | DATETIME        | Yes  | NULL              | INDEX   | Last successful authentication                       |
| revoked_at   | DATETIME        | Yes  | NULL              | INDEX   | Token revocation timestamp                           |
| revoked_by   | BIGINT UNSIGNED | Yes  | NULL              | INDEX   | User ID that revoked the token (if applicable)       |
| ip_address   | VARCHAR(45)     | Yes  | NULL              | -       | IP address where the token was generated             |
| user_agent   | TEXT            | Yes  | NULL              | -       | Browser/device information                           |
| metadata     | LONGTEXT        | Yes  | NULL              | -       | JSON metadata for future extensions                  |
| created_at   | DATETIME        | No   | CURRENT_TIMESTAMP | INDEX   | Creation timestamp                                   |
| updated_at   | DATETIME        | No   | CURRENT_TIMESTAMP | INDEX   | Last updated timestamp                               |

---

# Supported Token Types

## Version 1

```text
magic_login
```

## Reserved for Future

```text
password_reset
email_verification
account_invitation
api_access
```

The authentication system is extensible and new token types may be introduced without modifying the database schema.

---

# Redirect Behavior

After successful authentication, SupportBay redirects users to the stored destination.

Examples:

```text
/support/ticket/F6C521D5

/support/dashboard

/support/profile
```

If no redirect destination exists, users are redirected to their Support Dashboard.

---

# Token Lifecycle

## Generated

Authentication tokens may be generated when:

- A guest submits their first support ticket.
- A customer requests a new Magic Login link.
- An administrator manually sends a secure login link (future).
- Future authentication workflows require temporary access.

---

## Valid

A token is considered valid only if:

- The token exists.
- The associated user exists.
- The token has not expired.
- The token has not been revoked.

---

## Successful Authentication

After validation:

1. Authenticate the user using the WordPress authentication system.
2. Create the WordPress login session.
3. Update `last_used_at`.
4. Redirect the user to the stored destination.

---

## Revocation

Authentication tokens become invalid when:

- The customer creates a password.
- An administrator manually revokes access.
- The token expires.
- Future security policies require token invalidation.

By default, when a customer creates a password, **all active `magic_login` tokens for that customer are revoked**.

---

# Security Rules

SupportBay follows the following security principles:

- Never store raw authentication tokens.
- Store only SHA-256 hashes.
- Tokens must be generated using a cryptographically secure random generator.
- Compare hashes using constant-time comparison.
- Authentication endpoints should always require HTTPS.
- Token hashes are never exposed through the REST API.

---

# Expiration

Default expiration:

```text
30 days
```

Administrator configurable.

Recommended options:

- 1 Day
- 7 Days
- 30 Days
- Custom duration

Expired tokens cannot be used for authentication.

---

# Business Rules

- One customer may have multiple active authentication tokens.
- Tokens are reusable until they expire or are revoked.
- Password creation revokes all active Magic Login tokens.
- Authentication tokens are provider-independent.
- Authentication tokens are not linked to support providers.
- Redirect destinations are optional.
- Raw tokens are never stored.

---

# Repository Responsibilities

The Auth Token Repository is responsible for:

- Creating authentication tokens.
- Finding tokens by hash.
- Revoking tokens.
- Removing expired tokens.
- Updating usage timestamps.
- Retrieving active tokens for a customer.

Authentication logic belongs to the Authentication Service.

---

# Future Enhancements

Potential future features include:

- Password Reset
- Email Verification
- One-Time Login Links
- Trusted Devices
- Device Recognition
- QR Code Login
- API Access Tokens
- Login History
- Session Management
- Token Rotation

These features can be implemented without modifying the existing database schema.

---

# Approved Decisions

✓ Generic authentication token system

✓ Magic Login support (v1)

✓ Future-ready for password reset and email verification

✓ Secure SHA-256 token storage

✓ Multiple active tokens per customer

✓ Configurable expiration

✓ Redirect destination support

✓ WordPress authentication integration

✓ Password creation revokes all active Magic Login tokens

✓ Provider-independent architecture

---
