# SupportBay – React Architecture

---

# Purpose

The React Architecture defines how the SupportBay frontend application is structured, organized, and connected to the REST API.

It powers:

- Customer Support Portal
- Agent Dashboard
- Manager Dashboard
- Admin UI (settings, providers, logs)

The goal is to create a **modern SaaS-like experience inside WordPress**.

---

# Core Principle

React is NOT just UI.

It is:

> A full client-side application layer consuming SupportBay REST API

---

# Application Entry Point

```text id="r1"
assets/src/react/app.tsx
```

Build output:

```text id="r2"
assets/dist/supportbay-app.js
```

---

# Application Boot Flow

```text id="r3"
WordPress loads page
        ↓
React root container detected
        ↓
Bootstrap config injected (PHP → JS)
        ↓
React App initialized
        ↓
Router loads correct module
        ↓
API client fetches data
        ↓
UI rendered
```

---

# Global Architecture

```text id="r4"
React App
  ├── Core Layer
  ├── API Layer
  ├── Auth Layer
  ├── Router Layer
  ├── State Layer
  ├── Modules Layer
  └── UI Components Layer
```

---

# Folder Structure

```text id="r5"
react/
  app.tsx

  core/
    bootstrap/
    config/
    utils/

  api/
    client.ts
    endpoints.ts

  auth/
    auth-context.ts
    useAuth.ts

  router/
    routes.tsx
    guards.tsx

  state/
    stores/

  modules/
    tickets/
    messages/
    auth/
    dashboard/
    settings/
    providers/
    notifications/

  components/
    ui/
    layout/
    forms/
    tables/

  pages/
    customer/
    agent/
    admin/
```

---

# Multi-Role UI System

SupportBay UI adapts based on role:

## Roles

- Customer
- Agent
- Support Manager
- Administrator

---

## Route Behavior

```text id="r6"
/support → Customer Portal
/agent → Agent Dashboard
/admin → Admin Panel
```

Role detection happens via API.

---

# Routing System

Uses React Router:

```text id="r7"
BrowserRouter
```

Route Groups:

## Customer Routes

- /support/tickets
- /support/tickets/:id
- /support/new-ticket

## Agent Routes

- /agent/tickets
- /agent/tickets/:id
- /agent/assignments

## Admin Routes

- /admin/dashboard
- /admin/providers
- /admin/settings

---

# API Layer

Central API client:

```text id="r8"
api/client.ts
```

Responsibilities:

- HTTP requests
- authentication headers
- error normalization
- retry logic (future)

Example:

```ts id="r9"
api.get("/tickets");
api.post("/tickets", data);
```

---

# Authentication Layer

Handles:

- WordPress session auth
- Magic login token auth
- Role-based routing

---

## Auth Flow

```text id="r10"
Token / WP Session
        ↓
Validate via /auth/validate
        ↓
Set React Auth Context
        ↓
Load Role-based Routes
```

---

# State Management

We use a **lightweight store approach** (not heavy Redux by default).

Options:

- Context API (core)
- Lightweight store per module
- Optional Zustand (recommended for scaling)

---

## State Structure

```text id="r11"
auth-store
ticket-store
ui-store
notification-store
settings-store
```

---

# Module-Based Frontend System

Each backend module has a matching frontend module:

## Example:

### Tickets Module

```text id="r12"
modules/tickets/
  api.ts
  hooks.ts
  components/
  pages/
  store.ts
```

---

# Ticket UI Flow

```text id="r13"
Ticket List
    ↓
Ticket Detail
    ↓
Messages Thread
    ↓
Reply / Internal Note
```

---

# Component System

## UI Components (Reusable)

```text id="r14"
Button
Input
Modal
Table
Badge
Tabs
Dropdown
```

---

## Layout Components

```text id="r15"
AppLayout
Sidebar
Header
Topbar
AuthLayout
```

---

# Design Strategy

SupportBay UI is:

- SaaS-style
- Clean dashboard layout
- Minimal WordPress feel
- Role-based visibility

---

# Data Flow

```text id="r16"
React Component
      ↓
Hook (useTickets)
      ↓
API Client
      ↓
REST API
      ↓
Service Layer (PHP)
      ↓
Database
```

---

# Ticket Module Example

## Hook

```ts id="r17"
useTickets();
```

## API

```ts id="r18"
GET / tickets;
```

## Component Flow

```text id="r19"
TicketsPage
   ↓
TicketsTable
   ↓
TicketRow
```

---

# File Upload Handling

- Uses Attachments module API
- Supports chunk upload (future)
- Preview before submit

---

# Real-Time Updates (Future)

Planned:

- WebSocket / Pusher integration
- Live ticket updates
- Agent typing indicator
- Live notifications

---

# Performance Strategy

- Code splitting per module
- Lazy loading routes
- Memoized components
- API caching layer
- Virtualized tables (for tickets list)

---

# Error Handling

Global error handler:

- API failure fallback UI
- Toast notifications
- Retry actions
- Graceful degradation

---

# Notification System (Frontend)

Handles:

- success messages
- errors
- warnings
- ticket updates
- system alerts

---

# Build System

```text id="r20"
Webpack + TypeScript
```

Outputs:

```text id="r21"
supportbay-app.js
supportbay-admin.js
supportbay-customer.js
```

---

# Security Rules

- No sensitive data in frontend state
- Token stored securely (HTTP-only preferred when possible)
- Role-based route protection
- API permission always enforced server-side

---

# Extensibility

SupportBay React supports:

- Custom modules
- Plugin-based UI injection
- Hook-based UI extension system
- Third-party UI components (future marketplace)

---

# Approved Decisions

✓ Modular React architecture

✓ Role-based UI system

✓ Backend module mapping

✓ Central API client

✓ Lightweight state management

✓ SaaS-like UI inside WordPress

✓ Scalable frontend modules

✓ Clean separation of UI layers

---
