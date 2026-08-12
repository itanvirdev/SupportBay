# CURRENT_STATUS.md

# SupportBay Current Development Status

Last Updated

August 2026

Current Version

v1.0 Development

Current Branch

main

---

# Current Sprint

Customer Management Workspace

Current Objective

Build customer search, profiles, linked providers, purchase history, and ticket history.

---

# Overall Progress

Foundation

```
100%
```

Business Modules

```
95%
```

Integration Foundation

```
100%
```

Verification Module

```
100%
```

Envato Provider

```
70%
```

Customer Features

```
10%
```

Admin Features

```
5%
```

Overall

```
≈70%
```

---

# Completed Foundation

The following architecture is considered stable.

Core

```
✅ Container

✅ Foundation

✅ Database

✅ Repository Base

✅ Entity Base

✅ Events

✅ Event Dispatcher

✅ Event Listeners

✅ Testing Framework

✅ Flow Tests

✅ Migration Registry

✅ Service Provider Registry
```

---

# Completed Modules

The following modules have passing Flow Tests.

```
✅ Tickets

✅ Messages

✅ Departments

✅ Activities

✅ Attachments

✅ Customers

✅ Authentication

✅ Providers

✅ Verifications

✅ Notification Foundation
```

Unless a bug exists,

these modules should not be refactored.

---

# Current Architecture

Core

↓

Modules

↓

Integrations

↓

Providers

Business modules remain provider-independent.

Providers adapt external APIs.

---

# Current Integration Architecture

Completed

```
IntegrationProvider

PurchaseVerificationProvider

IntegrationManager

IntegrationRegistry

IntegrationDiscovery

PurchaseVerificationData
```

Concrete Providers

```
Envato
```

Future

```
EDD

WooCommerce

Freemius

Paddle

LemonSqueezy
```

---

# Current Verification Architecture

Completed

```
PurchaseVerificationSchema

Verification Entity

VerificationRepository

VerificationService

Verification Events

VerificationServiceProvider

VerificationFlowTest
```

Business Rules

One Verification

↓

Many Tickets

One Ticket

↓

One Verification

Verification remains provider-independent.

---

# Envato Provider Status

Completed

```
EnvatoProvider

EnvatoServiceProvider

EnvatoApiClient

EnvatoOAuthService

EnvatoPurchaseService

EnvatoCustomerService

OAuthRoutes

ProviderConfiguration

EnvatoCustomer

EnvatoPurchase

EnvatoToken

README

MANUAL_TESTING
```

Still Missing

```
Refresh Token Consumption
```

---

# Current Task

Completed

```
PurchaseVerificationProvider

↓

EnvatoProvider

↓

PurchaseVerificationData

↓

VerificationService

↓

Verification Repository

ProviderVerificationFlowTest
```

Current Result

VerificationService verifies purchases through registered integrations without knowing concrete provider classes.

---

# Completed Portal Authentication Experience

```
Unauthenticated Portal Entry

↓

WordPress Login Handoff

↓

Single-use Magic Login

↓

OAuth Portal Redirect

↓

Portal Logout

↓

Auth and React Portal Flow Tests
```

---

# Completed Email Notification Foundation

```
TicketCreated / MessageCreated Events

↓

Notification Listeners

↓

NotificationService

↓

NotificationChannel Contract

↓

WordPress Email Channel

↓

NotificationFlowTest
```

Current behavior

- Ticket creation emails the administrator and customer.
- Customer follow-up replies email the administrator.
- Agent replies email the customer.
- Initial ticket content and internal notes do not create duplicate or private emails.

Future notification work

- Delivery logs
- Queue and retries
- Editable templates and preview
- SMTP and external notification providers

---

# Completed REST API and Webhooks Foundation

```
WordPress Capability Policy

↓

Versioned Admin Ticket Routes

↓

Standard Responses and Pagination

↓

Domain Events

↓

WebhookData

↓

WebhookDispatcher Contract

↓

ApiWebhookFlowTest
```

Current API behavior

- Administrators can list and inspect tickets and messages.
- Administrators can add replies or internal notes.
- Administrators can close and reopen tickets.
- Anonymous and unauthorized requests are rejected.
- Ticket and message lifecycle events produce normalized webhook data.

Knowledge Base remains deferred until its documentation is provided.

---

# Completed Admin API Expansion

```
Customer API

↓

Department API

↓

Secret-safe Provider API

↓

Purchase Verification API

↓

Filtering and Pagination

↓

Expanded ApiWebhookFlowTest
```

All administrator resource routes are read-only in this milestone.

---

# Completed Admin API Mutations and Capability Model

```
Protected WordPress Roles

↓

SupportBay Capabilities

↓

Capability-specific REST Policies

↓

Customer and Department Mutations

↓

Provider Enable / Disable

↓

Provider-backed Verification Refresh / Revoke

↓

Expanded ApiWebhookFlowTest
```

Roles and capabilities are installed idempotently for both new and existing installations.

---

# Completed Admin Dashboard Foundation

```
WordPress Admin Menu

↓

Native Support Tickets, Reports, and Settings Pages

↓

Shared PHP Header Navigation

↓

Capability-gated Page Access

↓

Isolated React Admin Bundle

↓

Authenticated API Bootstrap

↓

WordPress-controlled Active Workspace Bootstrap

↓

ReactAdminFlowTest
```

The administrator UI is functional and intentionally uses foundation styling pending the later design pass.

---

# Immediate Next Tasks

1. ✅ Add the shared administrator/customer ticket list with server-backed search, filters, sorting, selection, and pagination.

2. ✅ Add administrator ticket conversation detail and internal-note visibility.

3. ✅ Add agent reply and internal-note forms.

4. ✅ Add close/reopen actions and React Flow Test coverage.

5. ✅ Add WordPress TinyMCE rich replies and internal notes with strict server-side sanitization.

6. ✅ Add the agent ticket sidebar with customer, ticket, verified-purchase, and activity context.

7. ✅ Add secure agent reply attachments, message attachment display, and composer cancellation.

8. ✅ Add authenticated agent downloads and submit-reply-and-close behavior.

9. ✅ Add event-driven assignment, department, priority, trash, and restore operations with activity logging.

10. ✅ Auto-assign an unassigned ticket to its first public staff responder.

11. ✅ Add server-side Ticket Queue Intelligence with accurate public-reply counts, Need Reply detection, customer/agent/department context, agent and department filters, and smart sorting.

12. ✅ Add capability-gated bulk assignment, unassignment, department, priority, trash, and restore actions with per-ticket events and partial-failure reporting.

13. ✅ Add same-customer ticket merging with transactional message and attachment re-parenting, recoverable source retirement, target queue repair, and audit activities.

14. ✅ Add manager-controlled ticket splitting with selective conversation and attachment transfer, transactional queue repair, related-ticket metadata, and audit activities.

15. ✅ Add Customer 360 profiles with safe identity data, masked provider connections, purchase and ticket history, recent ticket activity, and capability-gated lifecycle controls.

---

# Current Workflow

Target architecture

```
VerificationService

↓

IntegrationManager

↓

PurchaseVerificationProvider

↓

EnvatoProvider

↓

EnvatoPurchaseService

↓

Envato API

↓

PurchaseVerificationData

↓

Verification Entity

↓

Database
```

VerificationService must never import

```
EnvatoProvider

EnvatoPurchaseService

EnvatoPurchase
```

---

# Do NOT Refactor

The following systems are stable.

```
Container

Repository Base

Entity Base

Events

Tickets

Messages

Activities

Attachments

Customers

Authentication

Providers Module

Verification Foundation
```

Only modify them if fixing bugs.

---

# Current Development Rules

Continue using

- Dependency Injection

- Repository Pattern

- Service Layer

- Event Driven Architecture

- Provider-independent modules

- Flow Tests

Never

- Couple Verification to Envato

- Import Providers into Modules

- Duplicate business logic

- Skip Flow Tests

---

# Next Milestone

After Administrator Ticket Workspace

```
Customer Management Workspace
```

---

# When Starting a New Codex Session

Read

```
CURRENT_STATUS.md

↓

AGENTS.md

↓

ROADMAP.md

↓

MASTER_PLAN.md
```

Then inspect the repository.

Before editing code,

always report

1.

Current implementation.

2.

Missing implementation.

3.

Files to modify.

4.

Why those files.

Only then begin coding.

---

Status

```
Current Sprint

Customer Management Workspace

Next Target

Customer Management Workspace
```

---
