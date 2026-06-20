# SupportBay – Module System Architecture

---

# Purpose

The Module System defines how SupportBay is organized into independent, self-contained feature units.

Each module encapsulates:

- Services
- Repositories
- Controllers (REST API)
- Events / Hooks
- Assets (React / JS / CSS)
- Configuration

This ensures SupportBay remains:

- Scalable
- Maintainable
- Extensible
- Plugin-friendly

---

# Core Idea

Instead of a monolithic structure:

```text id="m1"
SupportBay/
  TicketService.php
  AuthService.php
  ProviderService.php
  NotificationService.php
```

We use modular architecture:

```text id="m2"
Modules/
  Tickets/
  Auth/
  Providers/
  Notifications/
```

Each module is self-contained.

---

# Module Structure

Every module follows a strict structure:

```text id="m3"
Modules/
  Tickets/
    Module.php
    Services/
    Repositories/
    Http/
      Controllers/
      Requests/
    Events/
    Database/
    Assets/
    React/
    Config/
```

---

# Module Responsibilities

Each module is responsible for:

- Its own business logic
- Its own database interaction (via repositories)
- Its own REST API endpoints
- Its own frontend components
- Its own events/hooks

---

# Supported Core Modules (v1)

## 1. Tickets Module

Handles:

- Ticket creation
- Ticket lifecycle
- Status changes
- Assignment logic

---

## 2. Messages Module

Handles:

- Ticket replies
- Internal notes
- Message threading
- Attachments linking

---

## 3. Auth Module

Handles:

- Magic login
- Token validation
- Session handling
- Guest onboarding

---

## 4. Providers Module

Handles:

- Envato integration
- Future marketplaces
- Provider registry communication

---

## 5. Notifications Module

Handles:

- Email sending
- Notification logs
- Queue processing
- Retry system

---

## 6. Departments Module

Handles:

- Department management
- Ticket routing rules
- Assignment rules

---

## 7. Activities Module

Handles:

- Ticket activity tracking
- Audit logs
- System events history

---

## 8. Attachments Module

Handles:

- File uploads
- File validation
- Storage management
- Access control

---

# Module Lifecycle

Each module follows this lifecycle:

```text id="m4"
1. Register
2. Boot
3. Load Services
4. Register Routes
5. Register Events
6. Load Assets
```

---

# Module Class

Each module must define a main entry class:

```php id="m5"
SupportBay\Modules\Tickets\Module
```

---

# Module Interface

All modules implement:

```php id="m6"
interface ModuleInterface
{
    public function register(Container $container);
    public function boot(Container $container);
}
```

---

# Module Registration Flow

```text id="m7"
Plugin Bootstrap
        ↓
Load Module Registry
        ↓
Register Each Module
        ↓
Boot Modules
        ↓
Initialize REST + React
```

---

# Module Isolation Rule

Each module must:

- NOT directly access another module's database tables
- NOT call other module services directly (unless via container)
- Communicate via events or service layer

---

# Inter-Module Communication

Modules communicate using:

## 1. Events (Preferred)

```php id="m8"
do_action('sbay.ticket.created', $ticket);
```

## 2. Service Container (Controlled)

```php id="m9"
app(TicketService::class);
```

## 3. Shared Contracts (Advanced)

Interfaces for cross-module contracts.

---

# Example Flow (Ticket Creation)

```text id="m10"
Tickets Module
    ↓
Creates Ticket
    ↓
Triggers Event: sbay.ticket.created
    ↓
Notifications Module listens
    ↓
Auth Module verifies user
    ↓
Activities Module logs event
```

---

# Module Events System

Each module can:

### Emit Events

```php id="m11"
do_action('sbay.ticket.created', $ticket);
```

### Listen to Events

```php id="m12"
add_action('sbay.ticket.created', function($ticket) {
    // handle notification
});
```

---

# Module Assets System

Each module owns its assets:

```text id="m13"
Modules/Tickets/Assets/js/
Modules/Tickets/Assets/css/
Modules/Tickets/React/
```

Compiled into:

```text id="m14"
assets/dist/tickets.js
assets/dist/tickets.css
```

---

# Module Configuration

Each module may have:

```text id="m15"
config.php
```

Example:

```php id="m16"
return [
    'auto_assign' => true,
    'max_replies' => 100,
];
```

---

# Module Dependencies

Modules may depend on:

- Core services
- Other modules (via contracts/events only)
- External providers

But NEVER hard dependency on internal implementation.

---

# Module Registry

SupportBay maintains a registry:

```php id="m17"
ModulesRegistry::register([
    TicketsModule::class,
    AuthModule::class,
    ProvidersModule::class,
]);
```

---

# Activation Flow

When plugin activates:

```text id="m18"
Register Modules
    ↓
Run Install Hooks
    ↓
Create Tables
    ↓
Initialize Default Settings
```

---

# Deactivation Flow

```text id="m19"
Stop Cron Jobs
Clear Cache
Disable Queues
```

---

# Uninstall Flow

```text id="m20"
Remove Tables (optional)
Delete Options (if selected)
Clean Logs
```

---

# Design Principles

## 1. Isolation First

Each module should function independently.

## 2. Event Driven

Communication should be event-based, not tightly coupled.

## 3. Service Oriented

All logic flows through services.

## 4. WordPress Compatible

Still respects WP hooks and lifecycle.

---

# Anti-Patterns

❌ Modules directly modifying each other's DB

❌ Cross-module class instantiation

❌ Business logic inside controllers

❌ Hard-coded dependencies

---

# Benefits

- Easy to extend SupportBay
- Plugin becomes "mini framework"
- Developers can add modules without touching core
- Cleaner debugging
- Scalable architecture for AI, chat, integrations

---

# Future Enhancements

- Auto-discovery modules
- Marketplace modules (third-party addons)
- Module enable/disable UI
- Versioned module updates
- Hot-swappable modules

---

# Approved Decisions

✓ Fully modular architecture

✓ Feature-based separation

✓ Event-driven communication

✓ Module lifecycle system

✓ Independent asset management

✓ Scalable plugin ecosystem foundation

---
