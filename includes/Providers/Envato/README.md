# Envato Provider

The Envato Provider is the first official integration for SupportBay. It provides OAuth authentication, customer retrieval, and purchase verification through the Envato API while keeping the SupportBay core completely provider agnostic.

---

# Purpose

The Envato Provider is responsible for communicating with the Envato API.

It should never contain business logic related to:

- Tickets
- Customers
- Purchase Verifications
- Authentication
- WordPress Users

Those responsibilities belong to SupportBay modules.

---

# Architecture

```text
SupportBay Core
        │
        ▼
Integration Manager
        │
        ▼
Envato Provider
        │
        ▼
Envato API
```

---

# Directory Structure

```text
Providers/
└── Envato/
    ├── Api/
    │   └── EnvatoApiClient.php
    │
    ├── Data/
    │   ├── EnvatoCustomer.php
    │   ├── EnvatoPurchase.php
    │   └── EnvatoToken.php
    │
    ├── Exceptions/
    │   └── EnvatoException.php
    │
    ├── Routes/
    │   └── OAuthRoutes.php
    │
    ├── Services/
    │   ├── EnvatoCustomerService.php
    │   ├── EnvatoOAuthService.php
    │   └── EnvatoPurchaseService.php
    │
    ├── EnvatoProvider.php
    ├── EnvatoServiceProvider.php
    └── README.md
```

---

# Components

## EnvatoProvider

Represents the Envato integration inside the Integration Registry.

Responsibilities:

- Provider metadata
- Provider lifecycle
- Enable / Disable state

---

## EnvatoServiceProvider

Registers all Envato services into the SupportBay container.

Responsibilities:

- Register services
- Register routes
- Boot integration

---

## EnvatoApiClient

Low-level HTTP client for communicating with the Envato API.

Responsibilities:

- HTTP requests
- Authorization headers
- JSON decoding
- Error handling

Does **not** contain business logic.

---

## EnvatoOAuthService

Handles the OAuth lifecycle.

Responsibilities:

- Generate authorization URL
- Exchange authorization codes
- Refresh access tokens
- Retrieve authenticated account

---

## EnvatoCustomerService

Retrieves customer information from Envato.

Returns:

- EnvatoCustomer

Does not create or manage WordPress users.

---

## EnvatoPurchaseService

Retrieves purchase information.

Returns:

- EnvatoPurchase

Does not create verification records.

---

## OAuthRoutes

Registers public OAuth routes.

Responsibilities:

- Login endpoint
- Callback endpoint

Business logic remains inside the service layer.

---

# Data Objects

The provider returns immutable data objects instead of raw API arrays.

Current objects:

```text
EnvatoCustomer

EnvatoPurchase

EnvatoToken
```

These objects are provider-specific and are not persisted directly.

---

# Configuration

Configuration is managed through the Provider module.

Values include:

- Client ID
- Client Secret
- Redirect URI

Configuration is accessed through:

```php
ProviderConfiguration
```

The provider never reads database records directly.

---

# Authentication Flow

```text
Customer

↓

OAuthRoutes

↓

EnvatoOAuthService

↓

EnvatoCustomerService

↓

SupportBay Customer Module

↓

Auth Module

↓

Customer Dashboard
```

---

# Purchase Verification Flow

```text
Ticket

↓

Purchase Verification Module

↓

EnvatoPurchaseService

↓

Envato API

↓

EnvatoPurchase

↓

Verification Snapshot
```

The Envato Provider does not know about tickets.

---

# Design Principles

The provider:

- Only communicates with Envato.
- Never accesses tickets directly.
- Never creates WordPress users.
- Never authenticates customers.
- Never stores verification records.

Its responsibility ends after returning provider data.

---

# Future Services

Possible future additions:

- ProductService
- SalesService
- CollectionService
- ThemeService
- AuthorService

---

# Future Providers

The same architecture should support:

- Easy Digital Downloads
- WooCommerce
- Freemius
- Paddle
- Lemon Squeezy
- Gumroad
- OpenAI
- Google
- GitHub

Each provider should implement the common Integration Provider contract.

---

# Testing

The provider includes an integration flow test.

```text
EnvatoFlowTest
```

The test validates:

- Provider registration
- Configuration loading
- OAuth services
- Customer retrieval
- Purchase retrieval
- API communication

---

# Approved Decisions

- Envato is the first official provider.
- Provider architecture is integration-agnostic.
- All provider services are container-managed.
- Provider data is returned as immutable data objects.
- Business logic remains inside SupportBay modules.
- Future providers must implement the same Integration Provider contract.
- The ticketing system remains completely provider independent.
