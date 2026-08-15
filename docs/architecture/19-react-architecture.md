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

Implemented administrator template workspace:

- shared Settings navigation for Email Notifications and Integrations
- predefined template selection by event and recipient
- active/inactive control
- subject, HTML, and plain-text editors
- click-to-insert placeholder controls
- non-persistent desktop/mobile preview
- save and reset-to-default actions
- validated WordPress test-email action
- installation-wide master email switch
- independent predefined event/recipient controls

The Settings Delivery Logs workspace uses protected administrator endpoints for paginated delivery history, safe diagnostic detail, and retry. Search and filters remain server-backed. Retry buttons are rendered only from server `can_retry` state, and the frontend model intentionally omits stored content, headers, payloads, and raw metadata.

The Reports workspace loads aggregate notification delivery data from the protected reporting API. It presents date/channel/event filters, delivery-health summaries, a zero-filled daily trend, and event/channel tables. The client model contains no recipient or message fields.

Reports uses a shared React workspace with Ticket Performance and Notification Delivery tabs. Ticket Performance loads protected aggregate metrics, reuses server-provided department and agent options, and provides date, department, agent, and priority filters. It renders ticket/response summary cards, a daily activity chart, and department/agent workload tables.

Both report workspaces provide CSV download controls only when the PHP bootstrap reports `canExportReports`. Downloads reuse the currently applied filters, receive their filenames from protected REST responses, and use short-lived browser object URLs. Server-side capability checks remain authoritative.

Settings includes a Ticket SLA workspace for priority-based calendar-minute first-response targets. Ticket Performance displays SLA compliance cards, configured targets, and response-time bands, and includes the same data in CSV exports. The interface explicitly avoids presenting these values as business-hours SLA calculations.

The shared ticket queue consumes SLA state calculated by PHP. Staff views add an SLA-state filter, due-first sorting, and state badges with due-time tooltips. Customer views use the same component but hide SLA controls and indicators. React does not calculate or infer SLA state.

The staff ticket composer exposes a lazy-loaded Saved Replies picker backed by the protected saved-reply API. It searches the active server-returned collection by title and readable content and can populate either the reply or internal-note draft. Replacing non-empty content requires explicit confirmation. Programmatic selection updates the WordPress TinyMCE instance, while final message content continues through the normal server-side rich-text sanitizer. Customer composers do not expose this staff tool.

Settings includes a capability-gated Saved Replies workspace. Administrators can search active and inactive records, create or edit sanitized rich text with WordPress TinyMCE, change availability, and delete with explicit confirmation. The browser treats rendered drafts as untrusted input; persistence and sanitization remain owned by the PHP service.

Saved reply placeholders use a server-advertised allowlist. Settings exposes click-to-insert canonical tokens, and the staff ticket conversation resolves them from its already-authorized context only when a reply is selected. Replacement values are HTML-escaped, unknown tokens remain visible, and the submitted message still passes through the normal server sanitizer.

Agent ticket detail exposes distinct Resolve, Close, and Reopen transitions. Resolution and closure hide the reply composer until the ticket is reopened. Customer ticket detail treats both resolved and closed states as finalized and offers the existing reopen action for either state.

All operations use the authenticated SupportBay REST client and WordPress REST nonce. Sanitized preview HTML comes from the server; raw draft HTML is never injected directly into the page.

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
