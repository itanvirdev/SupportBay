# SupportBay – Database Specification

## Table: `wp_sbay_purchase_verifications`

---

# Purpose

Stores purchase verification records for all supported providers.

The table is provider-agnostic and acts as the single source of truth for verified purchases.

A purchase verification may be linked to multiple support tickets.

Support providers (Envato, Easy Digital Downloads, WooCommerce, etc.) populate this table through their own integration layer.

---

# Relationships

```text
Provider
    │
    ▼
Purchase Verification
    │
    ├── Ticket A
    ├── Ticket B
    ├── Ticket C
    └── Ticket D
```

One purchase verification can be associated with many tickets.

Each ticket references exactly one purchase verification.

---

# Table Structure

| Column               | Type            | Null | Default           | Index   | Description                                                               |
| -------------------- | --------------- | ---- | ----------------- | ------- | ------------------------------------------------------------------------- |
| id                   | BIGINT UNSIGNED | No   | AUTO_INCREMENT    | PRIMARY | Internal verification ID                                                  |
| provider             | VARCHAR(50)     | No   | envato            | INDEX   | Verification provider (envato, edd, woocommerce, etc.)                    |
| provider_reference   | VARCHAR(255)    | No   | -                 | INDEX   | Provider-specific identifier (purchase code, order ID, license key, etc.) |
| customer_id          | BIGINT UNSIGNED | Yes  | NULL              | INDEX   | Linked WordPress customer                                                 |
| provider_customer_id | VARCHAR(255)    | Yes  | NULL              | INDEX   | Customer ID/username from provider                                        |
| product_id           | VARCHAR(255)    | Yes  | NULL              | INDEX   | Provider product identifier                                               |
| product_name         | VARCHAR(255)    | Yes  | NULL              | -       | Product title at verification time                                        |
| license_type         | VARCHAR(100)    | Yes  | NULL              | INDEX   | Regular, Extended, Lifetime, etc.                                         |
| support_expires_at   | DATETIME        | Yes  | NULL              | INDEX   | Support expiration date                                                   |
| purchased_at         | DATETIME        | Yes  | NULL              | -       | Original purchase date                                                    |
| verified_at          | DATETIME        | No   | CURRENT_TIMESTAMP | INDEX   | Initial verification date                                                 |
| last_checked_at      | DATETIME        | Yes  | NULL              | INDEX   | Last successful verification                                              |
| verification_status  | VARCHAR(20)     | No   | verified          | INDEX   | pending, verified, expired, invalid, revoked                              |
| verification_data    | LONGTEXT        | Yes  | NULL              | -       | JSON snapshot returned by provider                                        |
| metadata             | LONGTEXT        | Yes  | NULL              | -       | Internal JSON metadata                                                    |
| created_at           | DATETIME        | No   | CURRENT_TIMESTAMP | INDEX   | Record creation date                                                      |
| updated_at           | DATETIME        | No   | CURRENT_TIMESTAMP | INDEX   | Last updated                                                              |

---

# Supported Providers

Current (v1):

```text
envato
```

Planned:

```text
edd
woocommerce
freemius
paddle
gumroad
lemonsqueezy
custom
```

---

# Provider Reference

The meaning depends on the provider.

Examples:

| Provider               | provider_reference      |
| ---------------------- | ----------------------- |
| Envato                 | Purchase Code           |
| WooCommerce            | Order ID                |
| Easy Digital Downloads | License Key             |
| Freemius               | License ID              |
| Paddle                 | Subscription / Order ID |

SupportBay never assumes a specific format.

---

# Verification Status

Supported values:

```text
pending
verified
expired
invalid
revoked
```

Definitions:

- **pending** → Waiting for provider verification.
- **verified** → Successfully verified.
- **expired** → Purchase exists but support period expired.
- **invalid** → Purchase could not be verified.
- **revoked** → Purchase/license revoked or no longer valid.

---

# Verification Snapshot

`verification_data` stores the provider response at verification time.

Example:

```json
{
	"buyer": "johnsmith",
	"item_id": 123456,
	"item_name": "Rovix - Business Theme",
	"license": "Regular License",
	"supported_until": "2027-01-01",
	"purchase_date": "2025-06-18"
}
```

Purpose:

- Preserve historical verification data.
- Continue displaying purchase information if provider API is unavailable.
- Reduce unnecessary API requests.

---

# Customer Relationship

Verification records may optionally be linked to a WordPress customer.

```text
Customer
      │
      ▼
Purchase Verification
      │
      ▼
Multiple Tickets
```

A customer may own multiple verified purchases.

---

# Reverification

SupportBay may refresh verification data.

Updates:

- verification_status
- support_expires_at
- last_checked_at
- verification_data

The original verification record remains.

---

# Business Rules

- Every verification belongs to one provider.
- A provider reference should be unique within the same provider.
- One verification may be linked to multiple tickets.
- Tickets never store purchase information directly.
- Purchase information is displayed through the verification relationship.
- Verification snapshots should be preserved for historical accuracy.

---

# Index Strategy

Primary Index:

- id

Composite Unique Index:

- provider
- provider_reference

Secondary Indexes:

- customer_id
- provider_customer_id
- product_id
- license_type
- verification_status
- support_expires_at
- verified_at
- last_checked_at
- created_at

---

# Repository Responsibilities

The Purchase Verification Repository is responsible for:

- Creating verification records
- Finding existing verifications
- Updating verification status
- Refreshing provider data
- Returning verification snapshots
- Provider-independent querying

Provider-specific API logic belongs to the Provider Service.

---

# Future Enhancements

Potential additions:

- Multiple licenses per purchase
- Subscription renewals
- Automatic support expiration refresh
- Background verification jobs
- Webhook synchronization
- Provider-specific metadata extraction
- License transfer history
- Verification analytics

These should integrate without modifying the core schema.

---

# Approved Decisions

✓ Provider-agnostic architecture

✓ One verification may serve multiple tickets

✓ One ticket references one verification

✓ Verification snapshot preserved

✓ Composite uniqueness (provider + provider reference)

✓ Provider-independent repository

✓ Status lifecycle supported

✓ Future-ready for additional marketplaces

---
