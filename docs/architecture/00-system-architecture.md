# SupportBay – System Architecture

## Document Information

| Property     | Value                     |
| ------------ | ------------------------- |
| Document     | 00-system-architecture.md |
| Product      | SupportBay                |
| Version      | v1                        |
| Status       | Approved                  |
| Last Updated | June 2026                 |

---

# Vision

SupportBay is a modern support platform built on WordPress.

Its primary goal is to provide a unified support experience for digital product sellers while remaining scalable, extensible, and developer-friendly.

SupportBay is designed around modern software architecture principles while leveraging the strengths of the WordPress ecosystem.

The long-term vision is to support multiple digital commerce platforms through a common provider architecture without changing the core support system.

---

# Core Objectives

SupportBay should be:

- Fast
- Secure
- Modular
- Extensible
- Provider Agnostic
- API First
- React Powered
- WordPress Native

---

# Architectural Philosophy

SupportBay follows one simple principle:

> **WordPress provides the platform. SupportBay provides the application.**

WordPress is responsible for:

- User authentication
- Roles & capabilities
- Database access
- REST infrastructure
- Cron
- Media handling
- Localization
- Plugin lifecycle

SupportBay is responsible for:

- Ticket management
- Customer portal
- Purchase verification
- Providers
- Notifications
- Activity logs
- Business rules
- Automation
- AI features

Business logic should never depend directly on WordPress UI or administrative screens.

---

# High-Level Architecture

```text
                    Browser
                       │
        ┌──────────────┴──────────────┐
        │                             │
  Customer Portal              Admin Dashboard
       (React)                     (React)
        │                             │
        └──────────────┬──────────────┘
                       │
                  REST API Layer
                       │
                 Controllers
                       │
                  Service Layer
                       │
               Repository Layer
                       │
                Database Layer
```

---

# Domain-Driven Design

SupportBay is organized by business domains rather than technical layers.

Each domain owns its own:

- Models
- Services
- Repositories
- Controllers
- Validators
- Policies
- Events
- Exceptions

Domains communicate through services and events rather than direct dependencies.

---

# Core Domains

The initial business domains are:

- Core
- Customer
- Ticket
- Message
- Attachment
- Department
- Activity
- Notification
- Provider
- Authentication
- Settings
- Reports
- REST
- Admin

Future domains may include:

- AI Assistant
- Live Chat
- Automation
- Knowledge Base
- Billing
- Analytics
- Webhooks

---

# Provider Architecture

SupportBay uses a provider-based architecture.

The ticketing system never communicates directly with external commerce platforms.

Instead:

```text
Customer
        │
        ▼
 SupportBay Core
        │
        ▼
 Provider Registry
        │
 ┌──────┼───────────────┐
 ▼      ▼               ▼
Envato  WooCommerce    EDD
```

Each provider implements a common contract.

Version 1 ships with Envato only.

Future providers can be added without modifying the ticketing system.

---

# Request Lifecycle

A typical request flows through the system as follows:

```text
Browser
    │
    ▼
REST API
    │
    ▼
Controller
    │
    ▼
Service
    │
    ▼
Repository
    │
    ▼
Database
```

Controllers coordinate requests.

Services contain business rules.

Repositories handle data persistence.

---

# Application Bootstrap

The plugin initializes through a controlled bootstrap sequence.

```text
supportbay.php
        │
        ▼
Application
        │
        ▼
Kernel
        │
        ▼
Service Container
        │
        ▼
Module Loader
        │
        ▼
Registered Domains
```

Every domain is loaded through the module system.

No business module should bootstrap itself independently.

---

# Service Container

SupportBay uses dependency injection through a lightweight service container.

Responsibilities include:

- Registering services
- Resolving dependencies
- Managing shared instances

All services should be retrieved through the container instead of creating new instances directly.

---

# Event-Driven Design

SupportBay exposes internal events using WordPress actions and filters.

Examples include:

- Ticket created
- Ticket assigned
- Ticket resolved
- Purchase verified
- Attachment uploaded
- Customer registered

This allows third-party developers to extend functionality without modifying core code.

---

# Database Strategy

Business data is stored in dedicated SupportBay tables.

Examples:

- Tickets
- Messages
- Attachments
- Activities
- Departments
- Purchase Verifications
- Notification Logs
- Magic Tokens

WordPress core tables remain responsible for:

- Users
- User Meta
- Roles
- Capabilities

---

# Frontend Architecture

SupportBay uses React for interactive interfaces.

Applications are separated by responsibility:

- Admin Dashboard
- Customer Portal
- Settings
- Reports

Shared UI components should be reused whenever possible.

---

# Asset Pipeline

Development assets are managed with Webpack.

Source files:

```text
assets/src/
```

Compiled output:

```text
assets/
```

Compiled assets should never be edited manually.

---

# Security Principles

SupportBay follows a security-first approach.

Core principles include:

- Capability-based authorization
- Nonce validation
- Input sanitization
- Output escaping
- Secure file handling
- Token hashing
- Encrypted provider credentials
- Least privilege access

Security requirements apply to every module.

---

# Performance Principles

SupportBay should remain performant on both small and large installations.

Guidelines include:

- Lazy-load services where appropriate.
- Load assets only when needed.
- Cache expensive provider responses.
- Minimize database queries.
- Avoid unnecessary WordPress hooks.
- Keep REST responses lightweight.

---

# Extensibility

SupportBay is designed as a platform.

Extension points include:

- Actions
- Filters
- Service Interfaces
- Provider Interfaces
- Repository Interfaces
- Module Registration
- REST Endpoints

Future integrations should extend the platform rather than modify the core.

---

# Development Standards

SupportBay follows:

- WordPress Coding Standards
- PSR-12
- PSR-4 Autoloading
- SOLID Principles
- DRY
- KISS
- Domain-Driven Design
- Semantic Versioning

Every pull request should preserve these standards.

---

# Long-Term Roadmap

The architecture is designed to support future modules, including:

- Multiple commerce providers
- AI-powered support assistant
- Live Chat
- Knowledge Base
- Automation Rules
- SLA Management
- Customer Satisfaction Surveys
- Webhooks
- Public API
- Mobile Applications

These features should integrate through the existing architecture rather than introducing parallel systems.

---

# Project Documentation

This document serves as the architectural overview for the project.

Detailed implementation specifications are maintained in individual documents, including:

- Domain Model
- User Journeys
- Database Schema
- Attachments
- Departments
- Providers
- Notifications
- Authentication
- Plugin Architecture
- Future Module Specifications

Developers should begin with this document before reviewing module-specific documentation.

---

# Approved Principles

✓ Domain-driven architecture.

✓ Provider-based integration model.

✓ Modern PHP with WordPress.

✓ React-powered user interfaces.

✓ REST-first communication.

✓ Capability-based permissions.

✓ Service and repository layers.

✓ Event-driven extensibility.

✓ Security-first implementation.

✓ Performance-conscious design.

✓ Long-term scalability.

✓ Developer-friendly architecture.

---
