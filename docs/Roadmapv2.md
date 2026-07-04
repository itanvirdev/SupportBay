# SupportBay Development Roadmap

**Version:** v2
**Status:** Active Development

---

# Phase A — Planning & Architecture

## Completed

- ✅ Product Architecture
- ✅ User Journeys
- ✅ Provider Architecture
- ✅ Database Specifications
- ✅ User Roles & Capabilities
- ✅ Magic Login Specification
- ✅ Development Roadmap

---

# Phase B — Core Development

## B1. Project Foundation ✅

- Composer
- Plugin Bootstrap
- Application
- Service Container
- Module Registration
- Service Providers
- Activation / Deactivation
- Uninstall

---

## B2. Core Framework ✅

### Database

- ✅ Base Repository

### Entities

- ✅ Base Entity

### Events

- ✅ Event Dispatcher
- ✅ Event Contracts
- ✅ Listener Registration
- ✅ Event Service Provider

### Testing

- ✅ Assert Helper
- ✅ FlowTest Base Class

---

# Phase C — Domain Modules

## Tickets ✅

- Schema
- Entity
- Repository
- Service
- Events
- Flow Test

Status: **Complete**

---

## Messages ✅

- Schema
- Entity
- Repository
- Service
- Events
- Listeners
- Flow Test

Status: **Complete**

---

## Activities ✅

- Schema
- Entity
- Repository
- Service
- Activity Logging
- Flow Test

Status: **Complete**

---

## Attachments ✅

- Schema
- Entity
- Repository
- Service
- Upload Events
- Delete Events
- Activity Listeners
- Flow Test

Status: **Complete**

---

## Departments ✅

- Schema
- Entity
- Repository
- Service
- Flow Test

Status: **Complete**

---

## Customer Module ✅

- Customer Schema
- Customer Entity
- Customer Repository
- Customer Service
- Customer Events
- Customer Listeners
- Customer Flow Test

Customer states:

- Guest
- Registered
- Verified
- Suspended

Sources:

- Manual
- Guest Ticket
- Providers
- Admin

Status: **Complete**

---

## Auth Tokens Module ✅

- Token Repository
- Magic Token Entity
- Login Service
- Token Validation
- Token Expiration
- Token Revocation
- Activity Logging
- Flow Test

Status: **Complete**

---

## Provider Registry ✅

- Provider Contracts
- Provider Registry
- Provider Manager
- Provider Database
- Provider Discovery
- Provider Settings

---

## Envato Provider

- OAuth Login
- Customer Linking
- Purchase Verification
- Product Retrieval
- Support Expiration

---

# Current Milestone

## Purchase Verification 🚧

Next implementation:

- Verification Module
- Verification Repository
- Verification Service
- Verification Entity
- Verification Events

---

# Upcoming Milestones

## Notifications

- Email Notifications
- Queue Architecture
- Notification Templates
- Notification Events
- Delivery Logs

---

# Future Development

## REST API

- Tickets
- Messages
- Customers
- Departments
- Providers
- Authentication
- Settings

---

## React Dashboard

### Customer Portal

- Dashboard
- Ticket List
- Ticket Details
- Profile

### Staff Dashboard

- Ticket Queue
- Assignment
- Internal Notes
- Reports

### Administration

- Settings
- Providers
- Departments
- Roles & Capabilities

---

# Testing Progress

## Flow Tests

- ✅ TicketFlowTest
- ✅ MessageFlowTest
- ✅ ActivityFlowTest
- ✅ AttachmentFlowTest
- ✅ DepartmentFlowTest
- ✅ CustomerFlowTest
- ✅ AuthFlowTest
- ✅ ProviderFlowTest

Upcoming:

- ⏳ VerificationFlowTest
- ⏳ NotificationFlowTest

---

# Current Progress

| Area                  | Status      |
| --------------------- | ----------- |
| Core Framework        | ✅ Complete |
| Event System          | ✅ Complete |
| Tickets               | ✅ Complete |
| Messages              | ✅ Complete |
| Activities            | ✅ Complete |
| Attachments           | ✅ Complete |
| Departments           | ✅ Complete |
| Testing Framework     | ✅ Complete |
| Customers             | ✅ Complete |
| Auth                  | ✅ Complete |
| Provider Registry     | ✅ Complete |
| Envato Integration    | ✅ Complete |
| Purchase Verification | 🚧 Next     |
| Notifications         | Planned     |
| REST API              | Planned     |
| React Dashboard       | Planned     |

---

# Immediate Next Goal

> Build the **Customer Module**, as it becomes the central domain object connecting tickets, authentication, providers, purchase verification, and the customer portal.
