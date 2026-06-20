# SupportBay – Provider Architecture Specification (v1)

## Document Information

| Property     | Value                       |
| ------------ | --------------------------- |
| Document     | 11-provider-architecture.md |
| Product      | SupportBay                  |
| Version      | v1                          |
| Status       | Approved                    |
| Last Updated | June 2026                   |

---

# Purpose

The Provider Architecture allows SupportBay to verify purchases and authenticate customers using multiple digital commerce platforms through a unified interface.

The ticketing system must remain completely independent of any specific provider.

Version 1 ships with Envato only, but the architecture is designed to support additional providers without changing the core ticketing system.

---

# Core Principles

## Provider Agnostic

The SupportBay Core must never directly communicate with Envato APIs.

Instead:

Customer
↓
SupportBay Core
↓
Provider Manager
↓
Provider
↓
External Platform

---

## Pluggable Architecture

Every provider behaves like a plugin.

Examples:

- Envato
- Easy Digital Downloads
- WooCommerce
- Freemius
- Paddle
- Lemon Squeezy
- Gumroad

---

## One Interface

Every provider implements the same contract.

SupportBay Core never checks:

if provider == Envato

Instead it always communicates through the Provider Interface.

---

# Provider Lifecycle

Provider Registered

↓

Provider Enabled

↓

Provider Configured

↓

Provider Available

↓

Customer Authentication

↓

Purchase Verification

↓

Ticket Creation

---

# Provider Manager

The Provider Manager is responsible for:

- Registering providers
- Loading providers
- Returning provider instances
- Checking provider availability
- Executing provider actions

The Provider Manager is the only component that knows which providers exist.

---

# Provider Interface

Every provider must implement:

## Identity

get_id()

Returns:

envato

---

get_name()

Example:

Envato Market

---

get_version()

---

## Configuration

is_enabled()

has_valid_configuration()

get_settings()

---

## Authentication

supports_oauth()

authenticate()

disconnect()

refresh_token()

---

## Customer

get_customer()

get_avatar()

get_username()

---

## Purchase Verification

verify_purchase()

Returns:

Purchase Verification Object

---

get_products()

Returns:

Customer products.

---

get_purchase()

Returns:

Purchase details.

---

get_support_expiry()

Returns:

Support expiration date.

---

get_license_type()

Returns:

Regular

Extended

---

# Purchase Verification Object

Every provider returns a normalized object.

Fields:

- provider_id
- provider_user_id
- purchase_reference
- product_id
- product_name
- license_type
- purchased_at
- support_until
- buyer_name
- verified_at

SupportBay stores this object regardless of provider.

---

# Provider Registration

Providers register during plugin initialization.

Example:

Provider Manager

↓

Register Envato Provider

↓

Available Providers

---

# Provider Settings

SupportBay

↓

Settings

↓

Providers

---

Each provider receives its own settings page.

Example:

Providers

- Envato
- WooCommerce
- Easy Digital Downloads

---

# Provider Status

enabled

disabled

misconfigured

error

---

# Provider Dashboard

Display:

Provider Name

Status

Version

Authentication Type

Connection Status

Last Sync

---

# Customer Provider Links

One customer may connect multiple providers.

Examples:

Customer

↓

Envato

↓

WooCommerce

↓

Freemius

Each provider stores its own connection information.

---

# Purchase Verification

One verification belongs to one provider.

One verification may be referenced by multiple tickets.

Relationship:

Provider

↓

Verification

↓

Multiple Tickets

---

# Verification Flow

Customer submits purchase reference.

↓

Provider selected.

↓

Provider validates purchase.

↓

Normalized verification returned.

↓

SupportBay stores verification.

↓

Ticket references verification.

---

# OAuth Providers

Providers supporting OAuth should expose:

supports_oauth()

Authorization URL

Callback Handler

Token Refresh

Disconnect

---

# Non-OAuth Providers

Providers without OAuth may use:

- API Keys
- Purchase IDs
- License Keys
- Webhooks

SupportBay Core should not distinguish between authentication methods.

---

# Provider Capabilities

A provider may declare supported features.

Examples:

- OAuth Login
- Purchase Verification
- Product Sync
- License Validation
- Support Expiration
- Webhooks

UI should automatically adapt based on available features.

---

# Error Handling

Providers should return standardized exceptions.

Examples:

Invalid Credentials

Purchase Not Found

Expired License

API Rate Limit

Service Unavailable

SupportBay converts these into user-friendly messages.

---

# Future Provider Discovery

Future versions may support automatic provider registration.

Example:

Third-party plugin registers:

CustomProvider

↓

SupportBay automatically detects and loads it.

---

# Developer API

Developers should be able to:

- Register Providers
- Extend Existing Providers
- Override Provider Services
- Listen to Provider Events

---

# Future Providers

Planned integrations include:

- Envato
- Easy Digital Downloads
- WooCommerce
- Freemius
- Paddle
- Lemon Squeezy
- Gumroad
- Custom API Providers

---

# Recommendation: Provider Registry

Instead of the Provider Manager manually creating providers, let providers register themselves.

Example architecture:

```
SupportBay Core
        │
        ▼
Provider Registry
        │
 ┌──────┼──────────┐
 │      │          │
 ▼      ▼          ▼
Envato  EDD      WooCommerce
```

Then your code becomes something like:

```
SupportBay::providers()->get('envato');
SupportBay::providers()->all();
SupportBay::providers()->enabled();
```

This small design decision makes future integrations almost effortless and keeps the core plugin clean.

---

# Approved Decisions

✓ Provider-based architecture.

✓ Envato is the first provider.

✓ Ticket system remains provider agnostic.

✓ One provider interface.

✓ One verification belongs to one provider.

✓ One verification can be linked to multiple tickets.

✓ Provider Manager controls all providers.

✓ Providers expose capabilities.

✓ Providers have independent settings.

✓ Third-party providers can be added in future.

---
