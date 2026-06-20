# SupportBay – Plugin Architecture Specification (v1)

## Document Information

| Property     | Value                     |
| ------------ | ------------------------- |
| Document     | 12-plugin-architecture.md |
| Product      | SupportBay                |
| Version      | v1                        |
| Status       | Approved                  |
| Last Updated | June 2026                 |

---

# Purpose

This document defines the overall technical architecture for SupportBay.

The goal is to build a modern, scalable, maintainable WordPress plugin using contemporary PHP development practices while remaining fully compatible with WordPress.org requirements.

---

# Architecture Philosophy

SupportBay should be:

- Modular
- Domain Driven
- Object Oriented
- Extensible
- Testable
- PSR-4 Compliant
- Composer Managed

WordPress should be treated as the application framework, not the application architecture.

---

# Technology Stack

## Backend

- PHP 8.2+
- WordPress APIs
- Composer
- PSR-4 Autoloading

---

## Frontend

- React
- WordPress Components
- JavaScript (ESNext)
- SCSS
- Webpack

---

## Development

- Composer
- npm
- Webpack
- ESLint
- PHPCS
- PHPStan (recommended)
- GitHub Actions

---

# Root Directory Structure

```text
supportbay/
│
├── supportbay.php
├── uninstall.php
├── readme.txt
├── composer.json
├── package.json
├── webpack.config.js
├── phpcs.xml
├── phpstan.neon
├── .editorconfig
├── .gitignore
├── .github/
│
├── includes/
├── assets/
├── languages/
├── templates/
├── vendor/
└── tests/
```

---

# Includes Directory

The `includes` directory contains all PHP source code.

The project is organized by **business domain**, not by file type.

```text
includes/
│
├── Core/
├── Admin/
├── Customer/
├── Ticket/
├── Message/
├── Attachment/
├── Department/
├── Activity/
├── Notification/
├── Auth/
├── Provider/
├── Database/
├── REST/
├── CLI/
├── Helpers/
└── Shared/
```

Each domain owns its own services, repositories, controllers, validators, models, and business logic.

---

# Example Domain Structure

```text
Ticket/
│
├── Models/
├── Repositories/
├── Services/
├── Controllers/
├── Validators/
├── Policies/
├── Events/
├── DTO/
├── Exceptions/
└── TicketService.php
```

Every major business module should follow a similar structure.

---

# Core Module

The Core module is responsible for:

- Plugin bootstrap
- Service Container
- Module registration
- Configuration
- Dependency management
- Plugin lifecycle

Core should never contain business logic.

---

# Service Container

SupportBay uses a lightweight Dependency Injection Container.

Responsibilities:

- Register services
- Resolve dependencies
- Share singleton instances

Example:

```php
SupportBay::container()->get(TicketService::class);
```

Business classes should depend on interfaces whenever possible.

---

# Module Loader

Each domain module registers itself during plugin boot.

Example:

```text
Plugin Boot
    ↓
Core
    ↓
Module Loader
    ↓
Ticket Module
Message Module
Provider Module
Notification Module
```

Modules should not manually include files.

---

# Provider Architecture

Providers are isolated modules.

```text
Provider/
│
├── Contracts/
├── Manager/
├── Registry/
└── Providers/
    ├── Envato/
    ├── EDD/
    ├── WooCommerce/
    ├── Freemius/
    ├── Paddle/
    └── LemonSqueezy/
```

Each provider implements the same contract.

SupportBay Core never communicates directly with provider APIs.

---

# Namespace Structure

Base Namespace:

```text
SupportBay
```

Examples:

```text
SupportBay\Core
SupportBay\Ticket
SupportBay\Message
SupportBay\Attachment
SupportBay\Department
SupportBay\Provider
SupportBay\Auth
SupportBay\Notification
SupportBay\Activity
SupportBay\REST
```

Avoid unnecessary namespaces such as:

```text
SupportBay\Includes
```

---

# Asset Architecture

Compiled assets:

```text
assets/
│
├── css/
│   ├── admin/
│   ├── customer/
│   └── shared/
│
├── js/
│   ├── admin/
│   ├── customer/
│   └── shared/
│
├── images/
├── fonts/
└── vendor/
```

Source assets:

```text
assets/src/
│
├── scss/
├── js/
├── images/
└── fonts/
```

Webpack compiles source files into production-ready assets.

---

# React Applications

SupportBay should use multiple React applications instead of one large bundle.

Example:

```text
assets/src/js/admin/
│
├── dashboard/
├── settings/
├── reports/
├── providers/
└── roles/
```

Customer-facing applications:

```text
assets/src/js/customer/
│
├── portal/
├── tickets/
└── profile/
```

Shared components:

```text
assets/src/js/shared/
```

---

# Database Layer

All database access must be performed through repositories.

Avoid direct database queries inside controllers or services.

Example:

```text
Controller
    ↓
Service
    ↓
Repository
    ↓
Database
```

---

# REST API

REST controllers should contain only request handling.

Business logic belongs in services.

Example:

```text
REST Request
    ↓
Controller
    ↓
Service
    ↓
Repository
```

---

# Event System

SupportBay should expose internal events using WordPress actions and filters.

Examples:

- Ticket Created
- Ticket Assigned
- Ticket Closed
- Attachment Uploaded
- Purchase Verified

This enables third-party integrations without modifying core code.

---

# Configuration

Plugin configuration should be centralized.

Example:

```text
SupportBay\Config
```

Avoid hardcoded constants throughout the codebase.

---

# Templates

UI templates should be stored separately.

```text
templates/
│
├── customer/
├── emails/
├── admin/
└── shared/
```

Business logic must never be embedded in template files.

---

# Coding Standards

SupportBay follows:

- WordPress Coding Standards
- PSR-12
- SOLID Principles
- DRY
- KISS

---

# Error Handling

Use custom exception classes for domain-specific errors.

Examples:

- TicketNotFoundException
- VerificationFailedException
- ProviderUnavailableException

Never expose raw exception messages to end users.

---

# Testing

Recommended structure:

```text
tests/
│
├── Unit/
├── Integration/
└── Fixtures/
```

Target:

- Services
- Repositories
- Validators
- Provider implementations

---

# Performance Guidelines

- Lazy-load services where appropriate.
- Load assets only on required screens.
- Cache expensive provider/API responses.
- Batch database operations where practical.
- Avoid unnecessary WordPress hooks during every request.

---

# Extensibility

SupportBay should expose:

- WordPress Actions
- WordPress Filters
- Provider Interfaces
- Repository Interfaces
- Service Interfaces

Third-party developers should be able to extend functionality without modifying core files.

---

# Approved Decisions

✓ Composer with PSR-4 autoloading.

✓ Webpack for asset compilation.

✓ React for modern administrative and customer interfaces.

✓ Domain-driven module organization.

✓ Dependency Injection container.

✓ Service Container.

✓ Repository pattern.

✓ Service layer.

✓ Provider Registry.

✓ Provider Manager.

✓ REST controllers remain thin.

✓ Business logic separated from presentation.

✓ Event-driven extensibility.

✓ Modern PHP architecture built on top of WordPress.

---
