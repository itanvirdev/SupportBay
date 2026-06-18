# SupportBay – Plugin Lifecycle Architecture

---

# Purpose

The Plugin Lifecycle defines how SupportBay boots, initializes, upgrades, and shuts down inside WordPress.

It ensures all systems load in the correct order:

- Core bootstrap
- Service container
- Modules
- REST API
- React frontend
- Background tasks

It also handles:

- Activation
- Deactivation
- Uninstall
- Version upgrades
- Database migrations

---

# Core Principle

SupportBay must always behave deterministically:

> Same boot order → same system state → predictable behavior

---

# Lifecycle Overview

```text id="l1"
WordPress Loads Plugin
        ↓
supportbay.php (Main Entry)
        ↓
Bootstrap Class
        ↓
Container Initialization
        ↓
Module Registry Load
        ↓
Service Providers Register
        ↓
Modules Boot
        ↓
REST API Init
        ↓
React App Boot
        ↓
System Ready
```

---

# Main Plugin Entry File

```text id="l2"
supportbay.php
```

Responsibilities:

- Define constants
- Load Composer autoload
- Initialize Bootstrap class

```php id="l3"
SupportBay::boot();
```

---

# Bootstrap Class

Core class:

```text id="l4"
SupportBay\Core\Bootstrap
```

Responsibilities:

- Initialize container
- Register service providers
- Load modules
- Trigger lifecycle hooks

---

# Boot Sequence

```text id="l5"
1. Define constants
2. Load autoloader
3. Initialize container
4. Register core services
5. Load module registry
6. Register modules
7. Boot modules
8. Initialize REST API
9. Initialize frontend assets
10. Fire "system ready"
```

---

# WordPress Hooks Mapping

## plugins_loaded

Used for:

- initializing container
- registering providers

```php id="l6"
add_action('plugins_loaded', [Bootstrap::class, 'init']);
```

---

## init

Used for:

- loading modules
- registering services
- preparing system state

---

## rest_api_init

Used for:

- REST controllers registration

---

## wp_enqueue_scripts / admin_enqueue_scripts

Used for:

- React assets
- CSS/JS loading

---

# Activation Flow

Triggered on plugin activation:

```text id="l7"
register_activation_hook
        ↓
Run Installer
        ↓
Create Database Tables
        ↓
Set Default Options
        ↓
Register Initial Roles
        ↓
Initialize Modules
```

---

## Activation Class

```text id="l8"
SupportBay\Core\Activation
```

Responsibilities:

- Run migrations
- Seed default data
- Setup roles & capabilities
- Initialize provider registry

---

# Deactivation Flow

```text id="l9"
register_deactivation_hook
        ↓
Pause Cron Jobs
        ↓
Clear Temporary Cache
        ↓
Disable Queues
```

⚠️ No data is deleted on deactivation.

---

# Uninstall Flow

Triggered only when plugin is deleted.

```text id="l10"
uninstall.php
```

Responsibilities:

- Drop tables (optional setting)
- Remove options (if enabled)
- Clear logs
- Remove scheduled events

---

# Version Upgrade System

SupportBay uses version tracking:

```text id="l11"
option: sbay_version
```

---

## Upgrade Flow

```text id="l12"
Plugin Loaded
        ↓
Check Version
        ↓
If outdated:
    Run Migration Manager
        ↓
Execute Pending Migrations
        ↓
Update Version Option
```

---

## Migration System

Each version upgrade can have:

```text id="l13"
Migrations/
  1.0.1/
  1.0.2/
```

Each migration:

```php id="l14"
class CreateTicketsTable
{
    public function up() {}
    public function down() {}
}
```

---

# Module Boot Sequence

Modules are booted after container is ready:

```text id="l15"
Container Ready
        ↓
Load Modules
        ↓
Register Module Services
        ↓
Boot Module Logic
        ↓
Register Events
        ↓
Load Assets
```

---

# System Ready Event

After full initialization:

```php id="l16"
do_action('sbay/system/ready');
```

Used for:

- custom extensions
- third-party integrations
- post-bootstrap logic

---

# Error Handling Strategy

If something fails during boot:

- Log error to debug log
- Disable only affected module (not entire plugin)
- Continue boot process if possible

---

# Safe Boot Principle

SupportBay must:

> NEVER break entire WordPress admin due to one module failure

So:

- Module errors are isolated
- Container failures are critical (stop boot)
- REST failures are isolated
- UI failures fallback gracefully

---

# Performance Strategy

- Lazy-load modules when possible
- Delay heavy services until needed
- Avoid executing queries during bootstrap
- Cache container resolution map (future optimization)

---

# Global Boot Helper (Optional)

```php id="l17"
SupportBay::app();
```

Returns container instance:

```php id="l18"
SupportBay::app()->get(TicketService::class);
```

---

# Scheduled Tasks (Cron)

Lifecycle also registers:

- ticket reminders
- notification retries
- provider sync
- cleanup jobs

Hook:

```text id="l19"
wp_cron
```

Managed via:

```text id="l20"
SupportBay\Core\Scheduler
```

---

# Security Considerations

- No sensitive operations before `plugins_loaded`
- No direct access before capability checks
- REST routes require permission validation
- Token validation always server-side

---

# Lifecycle Diagram (Full System)

```text id="l21"
WordPress Load
      ↓
Plugin Entry
      ↓
Bootstrap
      ↓
Container Init
      ↓
Providers Registered
      ↓
Modules Loaded
      ↓
Services Booted
      ↓
REST API Ready
      ↓
React UI Loaded
      ↓
System Ready Event
```

---

# Approved Decisions

✓ Deterministic boot sequence

✓ Container-first initialization

✓ Module-based lifecycle

✓ Safe failure isolation

✓ Versioned migrations system

✓ WordPress hook alignment

✓ Event-driven "system ready"

✓ No destructive deactivation behavior

---
