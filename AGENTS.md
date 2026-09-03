# AGENTS.md

> **SupportBay AI Development Guide**
>
> This document is the primary development guide for all AI agents (ChatGPT, Codex, Cursor, Claude, etc.) working on the SupportBay codebase.
>
> Before modifying any code:
>
> 1. Read this document completely.
> 2. Preserve the existing architecture.
> 3. Never introduce shortcuts that violate the established patterns.
> 4. Prefer extending the architecture over bypassing it.

---

# Project Overview

SupportBay is a modern, modular WordPress support ticket platform.

It is designed around:

- Dependency Injection
- Service Providers
- Repository Pattern
- Domain Entities
- Event Driven Architecture
- Provider-based Integrations
- Strongly Typed PHP
- Future Marketplace Support

The architecture is intentionally provider-agnostic.

SupportBay itself never depends directly on Envato, EDD, WooCommerce, Paddle, LemonSqueezy, Freemius, or any external marketplace.

Instead every external system is connected through the Integration layer.

---

# Project Goals

SupportBay should become a complete support ecosystem.

Examples:

- Support Tickets
- Customer Portal
- Marketplace Verification
- OAuth Login
- AI Assistant
- Live Chat
- Email Support
- REST API
- Mobile API
- Provider Marketplace

The architecture must remain extensible.

---

# Supported PHP

Minimum:

```
PHP 8.1
```

Always use

```php
declare(strict_types=1);
```

---

# WordPress Compatibility

SupportBay follows native WordPress APIs whenever possible.

Examples:

- wpdb
- wp_json_encode()
- current_time()
- sanitize_key()
- sanitize_text_field()
- wp_unslash()

Do not introduce Laravel helpers.

Do not introduce Symfony components.

Keep WordPress compatibility.

---

# Namespace

```
SupportBay\
```

Example

```php
SupportBay\Core\Container\Container

SupportBay\Modules\Tickets\Services\TicketService

SupportBay\Providers\Envato\EnvatoProvider
```

---

# Plugin Structure

```
supportbay/

├── supportbay.php

├── AGENTS.md

├── docs/

├── assets/

├── includes/

└── languages/
```

---

# Includes Structure

```
includes/

├── Common/

├── Core/

├── Dev/

├── Modules/

└── Providers/
```

Each directory has a clear responsibility.

---

# Common

Contains reusable shared code.

Examples

```
Enums

Helpers

Traits

Utilities

Contracts
```

Modules may depend on Common.

Common never depends on Modules.

---

# Core

Contains the application framework.

Examples

```
Container

Database

Entities

Events

Foundation

Integrations

Testing
```

Core never contains business logic.

Core should remain reusable.

---

# Modules

Business logic lives here.

Examples

```
Tickets

Messages

Activities

Attachments

Customers

Categories

Auth

Providers

Verifications
```

Every module owns its own:

- Database
- Entity
- Repository
- Service
- Events
- Listeners

---

# Providers

Contains external integrations.

Examples

```
Envato

EDD

WooCommerce

Freemius

Paddle

LemonSqueezy
```

Providers communicate with external APIs.

They never contain ticket business logic.

---

# Foundation

Location

```
Core/Foundation/
```

Contains

```
ServiceProvider.php

ServiceProviderRegistry.php
```

These are framework service providers.

They are NOT external providers.

Do not confuse them.

---

# Dependency Injection

Everything should resolve through the Container.

Prefer constructor injection.

Example

```php
public function __construct(
    private readonly TicketRepository $repository,
    private readonly EventDispatcher $events,
) {}
```

Avoid creating dependencies manually.

Avoid new-ing services inside services.

---

# Service Providers

Every module registers itself through a Service Provider.

Example

```
TicketServiceProvider

MessageServiceProvider

VerificationServiceProvider
```

Registration responsibilities

- Bind repositories
- Bind services
- Register listeners

Nothing more.

---

# Entity Pattern

Every Entity extends

```php
SupportBay\Core\Entities\Entity
```

Every Entity follows exactly this order

```
Constructor

↓

toArray()

↓

Getters

↓

Domain Methods
```

Never mix this order.

Entities should remain immutable whenever possible.

Entities never talk to the database.

---

# Repository Pattern

Repositories extend

```php
SupportBay\Core\Database\Repository
```

Repositories are responsible only for persistence.

Every repository should contain

```
table()

hydrate()

findBySomething()

findWhereSomething()
```

CRUD methods belong to Base Repository.

Repositories must never

- call APIs
- dispatch events
- contain business logic

---

# Service Pattern

Services contain business logic.

Services coordinate

Repository

↓

Events

↓

Integrations

↓

Domain

Services may

- validate
- orchestrate
- dispatch events

Services should not directly execute SQL.

---

# Event Pattern

Every domain event extends

```
AbstractEvent
```

Events should carry

Entity

not

Array

Example

```
VerificationVerified

↓

Verification Entity
```

Do not dispatch events from repositories.

Dispatch only from Services.

---

# Listener Pattern

Listeners perform side effects.

Examples

```
Create Activity

Send Email

Send Notification

Sync Provider
```

Listeners should remain small.

---

# Database Philosophy

Every module owns its own schema.

Examples

```
TicketSchema

MessageSchema

PurchaseVerificationSchema

ProviderSchema
```

Every schema exposes

```php
tableName()

schema()
```

Do not hardcode table names elsewhere.

---

# Enum Philosophy

Use backed enums.

Examples

```
TicketStatus

TicketPriority

VerificationStatus

AuthTokenState

ProviderStatus
```

Avoid string comparisons throughout the codebase.

Use enums instead.

---

# Testing

SupportBay contains its own testing framework.

Location

```
Core/Testing/
```

Flow Tests extend

```
FlowTest
```

Assertions use

```
Assert
```

Flow tests verify complete business workflows.

---

# Coding Style

Always

- strict_types
- final classes
- readonly dependencies where appropriate
- named constructor arguments
- small methods
- descriptive PHPDoc

Avoid

- giant classes
- static business logic
- global state
- hidden dependencies

---

# Integration Philosophy

SupportBay communicates with external systems through Integrations.

Location

```
Core/Integrations/
```

Contains

```
Contracts

Data

IntegrationManager

IntegrationRegistry

IntegrationDiscovery
```

SupportBay modules depend on Integration Contracts.

Never on concrete providers.

Example

GOOD

```
PurchaseVerificationProvider
```

BAD

```
EnvatoProvider
```

inside VerificationService.

---

# Provider Philosophy

Concrete providers live in

```
Providers/
```

Example

```
Providers/

Envato/

EDD/

WooCommerce/
```

Providers adapt external APIs.

They do not implement SupportBay business rules.

---

# Current Architecture Status

Completed

```
Foundation

Container

Database

Events

Testing

Tickets

Messages

Categories

Activities

Attachments

Customers

Authentication

Providers

Verification Foundation
```

Currently In Progress

```
Envato Provider

Purchase Verification

Provider Verification Flow
```

Next Milestone

```
PurchaseVerificationProvider

↓

PurchaseVerificationData

↓

EnvatoProvider

↓

VerificationService

↓

Provider Verification Flow

↓

Ticket Verification

↓

Customer Portal
```

---

# AI Development Rules

When modifying code:

✔ Preserve architecture

✔ Preserve namespaces

✔ Preserve folder structure

✔ Use existing patterns

✔ Extend modules instead of bypassing them

✔ Prefer reusable abstractions

Never

✘ Rewrite working architecture

✘ Couple modules together

✘ Add provider-specific logic into generic modules

✘ Break Dependency Injection

✘ Duplicate business logic

✘ Introduce shortcuts that conflict with AGENTS.md

---

# ============================================================================

# MODULE ARCHITECTURE

# ============================================================================

Every business feature belongs to a Module.

A module owns its own:

- Database
- Enums
- Entities
- Repository
- Service
- Events
- Listeners
- Service Provider
- Testing

A module should be self-contained.

Never spread business logic across multiple unrelated modules.

---

# Standard Module Structure

```
Modules/

ModuleName/

├── Database/

├── Entities/

├── Enums/

├── Events/

├── Listeners/

├── Repositories/

├── Services/

├── Testing/

└── ModuleServiceProvider.php
```

Future folders may include

```
Contracts/

Policies/

ValueObjects/
```

---

# Database Rules

Every module owns its own schema.

Examples

```
TicketSchema

MessageSchema

ActivitySchema

AttachmentSchema

CustomerSchema

CategorySchema

ProviderSchema

PurchaseVerificationSchema

AuthTokenSchema
```

Every schema exposes

```php
tableName()

schema()
```

No SQL belongs inside repositories.

No table names should be duplicated.

---

# Entity Rules

Every Entity extends

```
SupportBay\Core\Entities\Entity
```

Standard order

```
Constructor

↓

toArray()

↓

Getters

↓

Domain Methods
```

Entities represent state.

Entities NEVER

- execute SQL
- dispatch events
- call APIs

Entities MAY

- expose helper methods

Examples

```
isOpen()

isClosed()

isVerified()

hasSnapshot()

canRefresh()
```

---

# Repository Rules

Repositories extend

```
Core\Database\Repository
```

Repositories are persistence only.

Typical repository

```
table()

hydrate()

findBy...

findWhere...
```

Repositories never

- validate
- dispatch events
- call providers
- contain business workflows

---

# Service Rules

Services own business logic.

Services coordinate

Repository

↓

Events

↓

Integrations

↓

Domain

Every module has one service.

Examples

```
TicketService

MessageService

CustomerService

AuthService

VerificationService

ProviderService
```

---

# Event Rules

Events describe domain changes.

Examples

```
TicketCreated

MessageCreated

VerificationVerified

AuthTokenCreated
```

Events carry

Entity

not arrays.

Events should remain immutable.

---

# Listener Rules

Listeners react to events.

Typical responsibilities

Create Activity

↓

Send Notification

↓

Update Statistics

↓

Synchronize Provider

Listeners should not contain business rules.

---

# ============================================================================

# TICKET MODULE

# ============================================================================

Purpose

Support conversations.

Status

Completed.

Primary Entity

```
Ticket
```

Primary Service

```
TicketService
```

Database

```
wp_sbay_tickets
```

Track IDs

Examples

```
9D980553

54E5DF43

AB9123CD
```

Track ID is public.

Internal ID remains private.

Relationship

```
Ticket

↓

Messages

↓

Attachments

↓

Activities
```

Every Ticket references

```
Purchase Verification

(optional)
```

through

```
purchase_verification_id
```

One Verification

↓

Many Tickets

---

# MESSAGE MODULE

Stores conversations.

Every Message belongs to exactly one Ticket.

Attachments belong to Messages.

Activities reference Messages.

---

# ATTACHMENT MODULE

Attachments belong to Messages.

Never directly to Tickets.

Future support

Images

Videos

ZIP

PDF

Audio

3D

Medical files

---

# ACTIVITY MODULE

Activities build the ticket timeline.

Current rule

Every Activity requires

```
ticket_id
```

Do NOT create Activities for

- OAuth
- Providers
- Verification

until they are attached to a Ticket.

Future

Activities may become polymorphic.

---

# CUSTOMER MODULE

Represents SupportBay customers.

Customer may authenticate through

WordPress

↓

OAuth

↓

Magic Login

Customer is provider-independent.

---

# AUTH MODULE

Purpose

Authentication.

Supports

Magic Login

↓

OAuth

↓

Future Passwordless Login

Primary Entity

```
AuthToken
```

Rules

Tokens stored hashed.

Single-use supported.

Expiration supported.

Revocation supported.

Never store raw tokens.

---

# ============================================================================

# PROVIDER MODULE

# ============================================================================

Purpose

Stores provider configuration.

Examples

Envato

EDD

WooCommerce

Paddle

OpenAI

Gemini

Provider Module

≠

Integration Layer

Provider Module

↓

Database

↓

Settings

↓

Status

↓

Admin UI

Integration Layer

↓

Runtime

↓

API

↓

OAuth

↓

Verification

Never confuse them.

---

# Provider Categories

Marketplace

AI

Notification

Payment

Storage

Other

---

# Provider Entity

Represents

Database configuration.

NOT

Runtime implementation.

---

# ============================================================================

# VERIFICATION MODULE

# ============================================================================

Purpose

Provider-independent purchase verification.

Verification module knows

Verification

Repository

Service

Events

Database

It does NOT know

Envato

EDD

WooCommerce

Paddle

LemonSqueezy

Freemius

---

# Verification Lifecycle

Pending

↓

Verified

↓

Expired

↓

Revoked

or

Invalid

---

# Verification Database

```
purchase_verifications
```

One Verification

↓

Many Tickets

Verification stores

Provider

Reference

Product

License

Support Expiry

Snapshot

Customer

---

# Snapshot Philosophy

Verification stores

Provider Snapshot

This preserves historical information.

Future provider changes

must never alter

historical ticket data.

---

# ============================================================================

# INTEGRATION ARCHITECTURE

# ============================================================================

Core/

Integrations/

Contains

```
Contracts

Data

IntegrationManager

IntegrationRegistry

IntegrationDiscovery
```

Purpose

Normalize every provider.

SupportBay

↓

Integration Contract

↓

Concrete Provider

↓

External API

Never

SupportBay

↓

Envato directly

---

# Integration Contracts

Current

```
IntegrationProvider

PurchaseVerificationProvider
```

Future

OAuthProvider

LicenseProvider

SubscriptionProvider

CustomerProvider

NotificationProvider

StorageProvider

AIProvider

---

# Data Objects

Current

```
PurchaseVerificationData
```

Future

CustomerData

TokenData

SubscriptionData

LicenseData

ProductData

All providers normalize into these objects.

Modules never consume

provider-specific DTOs.

---

# ============================================================================

# ENVATO PROVIDER

# ============================================================================

Current Structure

```
Providers/

Envato/

Api/

Data/

Exceptions/

Routes/

Services/

Testing/

README.md

MANUAL_TESTING.md
```

EnvatoProvider implements

```
IntegrationProvider

PurchaseVerificationProvider
```

Responsibilities

OAuth

↓

Purchase Verification

↓

Customer Retrieval

↓

Token Refresh

↓

Provider Mapping

Never

Create Tickets

Create Verification Records

Create Activities

Authenticate WordPress directly

Those belong to SupportBay Services.

---

# ============================================================================

# FLOW TESTS

# ============================================================================

Every module owns its own Flow Test.

Current

```
TicketFlowTest

MessageFlowTest

CategoryFlowTest

ActivityFlowTest

AttachmentFlowTest

CustomerFlowTest

AuthFlowTest

ProviderFlowTest

VerificationFlowTest
```

Flow Tests verify

Complete business workflow

not

Individual methods.

Assertions use

```
Assert
```

Execution

```
?sbay_test=ticket

?sbay_test=auth

?sbay_test=provider

?sbay_test=verification
```

Flow Tests should never

Call external APIs.

Use Fake Providers whenever possible.

---

# CURRENT DEVELOPMENT STAGE

Completed

✅ Foundation

✅ Container

✅ Database

✅ Events

✅ Testing

✅ Tickets

✅ Messages

✅ Activities

✅ Attachments

✅ Categories

✅ Customers

✅ Auth

✅ Providers

✅ Verification Foundation

Current Milestone

🚧 Provider-driven Purchase Verification

Current Work

PurchaseVerificationProvider

↓

PurchaseVerificationData

↓

EnvatoProvider

↓

VerificationService

↓

Provider Verification Flow

Next Major Milestone

Ticket Verification Integration

↓

OAuth Login

↓

Customer Dashboard

↓

REST API

↓

Admin UI

↓

Email Notifications

# ============================================================================

# CODING STANDARDS

# ============================================================================

SupportBay follows modern PHP architecture.

Always use

```php
declare(strict_types=1);
```

Every class should be

```php
final class
```

unless inheritance is intentionally required.

Always use

- constructor promotion
- readonly dependencies
- named constructor arguments
- typed properties
- backed enums

Avoid

- public mutable properties
- static business logic
- helper classes with unrelated methods
- global state

---

# PHP STYLE

Preferred

```php
public function create(
    array $data,
): Verification {
}
```

Not

```php
public function create(array $data){
}
```

Always keep braces on new lines consistent.

---

# NAMING CONVENTIONS

Database

```
purchase_verifications

auth_tokens

tickets

messages
```

Schema

```
PurchaseVerificationSchema

AuthTokenSchema

TicketSchema
```

Entity

```
Verification

Ticket

Message

Customer
```

Repository

```
VerificationRepository

TicketRepository
```

Service

```
VerificationService

TicketService
```

Events

```
VerificationCreated

TicketClosed

MessageCreated
```

Listeners

```
LogVerificationCreated

CreateTicketActivity

SendCustomerNotification
```

Enums

```
VerificationStatus

TicketPriority

ProviderCategory

AuthTokenType
```

Flow Tests

```
VerificationFlowTest

TicketFlowTest

AuthFlowTest
```

Never abbreviate names.

Always prefer explicit naming.

---

# DATABASE RULES

Never execute raw SQL outside Repository.

Never hardcode

```
wp_sbay_xxx
```

Always use

```php
Schema::tableName()
```

Schema owns SQL.

Repository owns persistence.

Service owns business.

---

# JSON STORAGE

Always use

```php
wp_json_encode()
```

Never

```
json_encode()
```

for WordPress database values.

---

# DATES

Always use

```php
current_time('mysql')
```

Never

```
date()

gmdate()

time()
```

for stored timestamps.

Only use Unix timestamps for comparisons.

---

# SANITIZATION

Provider slug

```
sanitize_key()
```

Text

```
sanitize_text_field()
```

Textarea

```
sanitize_textarea_field()
```

URLs

```
esc_url_raw()
```

Always sanitize external data.

---

# SECURITY

Never store

OAuth tokens

Purchase Codes

Secrets

Passwords

in plain text.

Future

Secrets should be encrypted.

Purchase Codes should be masked.

Auth Tokens should remain hashed.

---

# DEPENDENCY INJECTION

Everything resolves from

Container.

Never create services manually.

Good

```php
$container->get(
    TicketService::class
);
```

Bad

```php
new TicketService(...)
```

inside application code.

---

# EXCEPTIONS

Business failures

↓

RuntimeException

Validation failures

↓

InvalidArgumentException

Never silently ignore failures.

---

# LOGGING

Business actions

↓

Events

↓

Listeners

↓

Activities

Never log directly inside repositories.

---

# EVENT DISPATCHING

Correct

Service

↓

Dispatch Event

↓

Listeners

Wrong

Repository

↓

Dispatch Event

---

# PROVIDER RULES

Concrete Providers

Never know

Tickets

Messages

Activities

Customers

They only know

External API

↓

Normalize Data

↓

Return DTO

---

# DTO RULES

Integration Data Objects

Must remain immutable.

Examples

PurchaseVerificationData

CustomerData

TokenData

ProductData

DTOs normalize provider responses.

---

# FLOW TEST RULES

Flow Tests verify

Business workflow

NOT

internal implementation.

Flow Tests should remain

Fast

Deterministic

Independent

Avoid

Real APIs

Real OAuth

Real external requests

Use Fake Providers whenever possible.

---

# FUTURE PROVIDERS

Planned

Marketplace

```
Envato

EDD

WooCommerce

Freemius

Paddle

LemonSqueezy

Gumroad
```

AI

```
OpenAI

Gemini

Claude
```

Notification

```
SMTP

Slack

Discord

Telegram
```

Storage

```
Amazon S3

Cloudflare R2

DigitalOcean Spaces
```

Each provider implements

Integration Contracts.

---

# FUTURE MODULES

Customer Portal

Billing

Licenses

Downloads

Notifications

REST API

Webhooks

Email Queue

Live Chat

AI Assistant

Knowledge Base

Analytics

Reports

Settings UI

Provider Marketplace

Everything should follow the existing architecture.

Never introduce shortcuts.

---

# CURRENT ROADMAP

Completed

```
✔ Foundation

✔ Container

✔ Database

✔ Events

✔ Testing

✔ Tickets

✔ Messages

✔ Categories

✔ Activities

✔ Attachments

✔ Customers

✔ Authentication

✔ Provider Module

✔ Verification Foundation
```

Current

```
Purchase Verification

↓

Provider Verification

↓

Envato Integration
```

Next

```
Ticket Verification

↓

OAuth Login

↓

Customer Dashboard

↓

REST API

↓

Admin UI

↓

Email Notifications

↓

Knowledge Base

↓

AI Assistant
```

---

# ARCHITECTURAL PRINCIPLES

SupportBay follows

Clean Architecture

↓

Domain Driven Design

↓

Repository Pattern

↓

Dependency Injection

↓

Event Driven Design

↓

Provider Based Integrations

The architecture should remain modular.

Business modules must never become tightly coupled.

Every module should be independently testable.

---

# AI INSTRUCTIONS

When working on SupportBay:

Always

✔ Read AGENTS.md first.

✔ Preserve architecture.

✔ Reuse existing abstractions.

✔ Prefer composition over duplication.

✔ Keep modules provider-independent.

✔ Add Flow Tests for new workflows.

✔ Update documentation after major architectural changes.

Never

✘ Rewrite working architecture.

✘ Break Dependency Injection.

✘ Add provider-specific logic to generic modules.

✘ Duplicate business logic.

✘ Skip Flow Tests.

✘ Introduce hidden dependencies.

If unsure,

choose consistency over convenience.

---

# PROJECT VISION

SupportBay is not intended to become only a WordPress ticket plugin.

The long-term goal is to build a modular customer support platform capable of supporting multiple marketplaces, SaaS products, plugins, themes, AI integrations, and external services through a unified provider architecture.

Every architectural decision should move the project closer to that vision while maintaining simplicity, modularity, and long-term maintainability.

============================================================================

End of AGENTS.md

Version: 1.0

Maintained by:
SupportBay Development Team

Architecture:
Tanvir Ahamed + OpenAI ChatGPT

============================================================================
