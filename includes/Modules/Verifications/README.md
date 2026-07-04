# Verification Module

The Verification module is responsible for managing verified customer purchases across all supported providers.

It provides a provider-agnostic layer that allows SupportBay to verify ownership of products, store verification snapshots, determine support eligibility, and associate verified purchases with support tickets.

The module does **not** communicate directly with external marketplaces. All provider communication is delegated to the Integration layer.

---

# Purpose

The Verification module acts as the single source of truth for verified purchases.

Its responsibilities include:

- Creating verification records
- Updating verification status
- Storing provider snapshots
- Determining support status
- Linking verified purchases to customers
- Linking verified purchases to multiple tickets
- Providing provider-independent access to verification data

---

# Architecture

```text
Customer
        │
        ▼
Verification Module
        │
        ▼
Integration Manager
        │
        ▼
Integration Provider
        │
        ▼
External Provider
```

The Verification module depends only on the Integration Manager and Integration Provider contract.

It never depends on provider-specific implementations.

---

# Directory Structure

```text
Modules/
└── Verifications/
    ├── Database/
    │   └── PurchaseVerificationTable.php
    │
    ├── Entities/
    │   └── Verification.php
    │
    ├── Enums/
    │   ├── VerificationStatus.php
    │   ├── SupportStatus.php
    │   └── VerificationSource.php
    │
    ├── Events/
    │
    ├── Listeners/
    │
    ├── Repositories/
    │   └── VerificationRepository.php
    │
    ├── Services/
    │   └── VerificationService.php
    │
    ├── Testing/
    │   └── VerificationFlowTest.php
    │
    ├── README.md
    └── VerificationServiceProvider.php
```

---

# Responsibilities

The Verification module is responsible for:

- Creating verification records
- Refreshing verification information
- Determining verification status
- Determining support status
- Returning verification snapshots
- Linking customers
- Associating multiple tickets with a single verification

The module is **not** responsible for:

- OAuth authentication
- Provider API communication
- Customer authentication
- Ticket management
- Provider configuration

Those responsibilities belong to other SupportBay modules.

---

# Verification Lifecycle

```text
Customer

↓

Provider Verification

↓

Verification Created

↓

Snapshot Stored

↓

Ticket Linked

↓

Verification Refreshed

↓

Support Status Updated
```

---

# Provider Independence

The Verification module never communicates directly with:

- Envato
- Easy Digital Downloads
- WooCommerce
- Freemius
- Paddle
- Lemon Squeezy
- Gumroad

Instead, it requests verification data through the Integration Manager.

```text
VerificationService

↓

IntegrationManager

↓

IntegrationProvider

↓

Provider Implementation
```

This allows new providers to be added without modifying the Verification module.

---

# Verification Entity

The Verification entity represents a verified purchase inside SupportBay.

Typical information includes:

- Provider
- Provider reference
- Customer
- Product
- License
- Support expiration
- Verification status
- Provider snapshot

The entity represents SupportBay's domain model and is independent of any provider API response.

---

# Verification Snapshot

Every successful verification stores a provider snapshot.

A snapshot preserves the provider response at the time of verification.

Benefits include:

- Historical accuracy
- Reduced provider API requests
- Continued operation when provider APIs are unavailable
- Consistent ticket history

Snapshots are never modified directly by other modules.

---

# Verification Status

The module supports the following verification states:

- Pending
- Verified
- Expired
- Invalid
- Revoked

These represent the validity of the verification itself.

---

# Support Status

Support status is managed independently from verification status.

Possible values include:

- Active
- Expired
- Unknown

A verification may remain valid even when support has expired.

---

# Customer Relationship

A verification may optionally be linked to a SupportBay customer.

One customer may own multiple verifications.

```text
Customer

↓

Verification A

Verification B

Verification C
```

---

# Ticket Relationship

One verification may be associated with multiple support tickets.

Each ticket references exactly one verification.

```text
Verification

├── Ticket A

├── Ticket B

├── Ticket C

└── Ticket D
```

Purchase information is never duplicated inside tickets.

---

# Events

The module will publish domain events for significant lifecycle changes.

Examples include:

- VerificationCreated
- VerificationUpdated
- VerificationExpired
- VerificationRevoked

These events allow other modules to react without creating direct dependencies.

---

# Testing

The module includes an integration flow test.

```text
VerificationFlowTest
```

The flow test validates:

- Verification creation
- Repository persistence
- Snapshot storage
- Customer association
- Ticket association
- Status updates
- Support status
- Refresh operations

---

# Future Enhancements

Potential future features include:

- Automatic reverification
- Scheduled background synchronization
- Provider webhooks
- License transfer history
- Subscription renewals
- Verification analytics
- Multiple licenses per verification

These additions should integrate without changing the core architecture.

---

# Approved Decisions

- Verification is provider agnostic.
- One verification may be linked to multiple tickets.
- One ticket references exactly one verification.
- Provider snapshots are preserved.
- External providers communicate through the Integration layer.
- Verification remains independent of ticket and customer implementations.
- Future providers require no changes to the Verification module.
