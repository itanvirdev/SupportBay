# SupportBay – REST API Architecture

---

# Purpose

The SupportBay REST API provides a structured communication layer between:

- React frontend (Customer, Agent, Admin dashboards)
- Backend PHP services
- External integrations (future AI, chat, etc.)

It ensures:

- Consistent data format
- Secure access control
- Modular endpoint structure
- Scalable API versioning

---

# Base URL

```text id="r1"
/wp-json/sbay/v1/
```

Versioning is mandatory to ensure backward compatibility.

---

# Core Principles

- Service layer is always used (no direct DB access)
- Every request is permission-checked
- Standard response structure
- No business logic inside controllers
- Module-based endpoint registration

---

# API Structure Overview

```text id="r2"
Tickets Module:
  /tickets
  /tickets/{id}
  /tickets/{id}/close
  /tickets/{id}/reopen

Messages Module:
  /tickets/{id}/messages

Auth Module:
  /auth/login-link
  /auth/validate
  /auth/logout

Providers Module:
  /providers
  /providers/{slug}/test

Departments Module:
  /departments

Notifications Module:
  /notifications/logs
```

---

# Standard Response Format

All API responses follow a strict structure:

## Success Response

```json id="r3"
{
	"success": true,
	"message": "Ticket created successfully",
	"data": {},
	"meta": {}
}
```

---

## Error Response

```json id="r4"
{
	"success": false,
	"message": "Validation failed",
	"error_code": "VALIDATION_ERROR",
	"errors": {
		"subject": "Subject is required"
	}
}
```

---

# Authentication Strategy

SupportBay uses **mixed authentication layers**:

## 1. WordPress Auth (Logged-in users)

Used for:

- Admin
- Agents
- Authenticated customers

Checked via:

```php id="r5"
is_user_logged_in()
current_user_can()
```

---

## 2. Token-Based Auth (Magic Link)

Used for:

- Guest login via email link
- Passwordless access

Validated via:

- `wp_sbay_auth_tokens`

---

## 3. Capability Layer

Every endpoint checks capabilities:

```text id="r6"
customer
agent
manager
administrator
```

---

# Permission Model

Each endpoint defines:

```php id="r7"
permission_callback()
```

Example:

```php id="r8"
if (!current_user_can('sbay_view_tickets')) {
    return false;
}
```

---

# Ticket Endpoints

## Create Ticket

```http id="r9"
POST /tickets
```

### Request

```json id="r10"
{
	"subject": "Login issue",
	"description": "Cannot login",
	"department_id": 1,
	"priority": "normal"
}
```

---

## Get Ticket

```http id="r11"
GET /tickets/{id}
```

---

## List Tickets

```http id="r12"
GET /tickets
```

Supports filters:

- status
- department
- customer
- assigned agent
- priority

---

## Update Ticket Status

```http id="r13"
POST /tickets/{id}/status
```

---

## Assign Ticket

```http id="r14"
POST /tickets/{id}/assign
```

---

## Close Ticket

```http id="r15"
POST /tickets/{id}/close
```

---

## Reopen Ticket

```http id="r16"
POST /tickets/{id}/reopen
```

---

# Message Endpoints

## Get Messages

```http id="r17"
GET /tickets/{id}/messages
```

---

## Send Message

```http id="r18"
POST /tickets/{id}/messages
```

Request:

```json id="r19"
{
	"message": "Here is my reply",
	"type": "reply"
}
```

Types:

- reply
- internal_note

---

# Auth Endpoints

## Generate Magic Login Link

```http id="r20"
POST /auth/login-link
```

---

## Validate Token

```http id="r21"
GET /auth/validate?token=xxx
```

---

## Logout

```http id="r22"
POST /auth/logout
```

---

# Provider Endpoints

## List Providers

```http id="r23"
GET /providers
```

---

## Test Provider Connection

```http id="r24"
POST /providers/{slug}/test
```

---

## Update Provider Settings

```http id="r25"
POST /providers/{slug}
```

---

# Department Endpoints

```http id="r26"
GET /departments
POST /departments
PUT /departments/{id}
DELETE /departments/{id}
```

---

# Notification Endpoints

## Get Logs

```http id="r27"
GET /notifications/logs
```

Supports:

- ticket_id
- status
- channel
- date range

---

# Error Handling Rules

All errors must:

- Use HTTP status codes correctly
- Include `error_code`
- Include readable message
- Never expose system internals

---

# Rate Limiting (Future Ready)

Planned:

- Guest ticket creation limits
- Login attempt throttling
- API abuse protection

---

# Security Rules

- All endpoints validated via permission callbacks
- Token endpoints must use SHA-256 validation
- No raw database access in controllers
- No sensitive data in error responses
- Input validation required for all POST/PUT requests

---

# Controller Architecture

Each module registers its own controllers:

```text id="r28"
Modules/
  Tickets/
    Http/
      Controllers/
        TicketController.php
```

Controllers:

- Only handle HTTP request/response
- Call Services only
- Never touch repositories directly

---

# API Versioning Strategy

Current:

```text id="r29"
v1
```

Future:

```text id="r30"
v2 (breaking changes only)
```

---

# Frontend Integration (React)

React communicates via:

```text id="r31"
SupportBay API Client
```

Responsibilities:

- Request abstraction
- Auth token handling
- Error normalization
- Caching (future)

---

# Event Hooks in API

API triggers internal events:

```php id="r32"
do_action('sbay.api.ticket.created', $ticket);
```

Used by:

- Notifications module
- Activities module
- Providers module

---

# Performance Strategy

- Minimal payload responses
- Lazy-loaded relations
- Pagination required for lists
- Avoid heavy joins
- Cache repeated queries (future)

---

# Approved Decisions

✓ Modular REST API per feature

✓ Strict service-layer access only

✓ Dual authentication system (WP + token)

✓ Standard response structure

✓ Event-driven API side-effects

✓ Versioned endpoints

✓ Module-based controller architecture

✓ React-first API design

---
