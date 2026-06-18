# SupportBay – Project Bootstrap Specification (v1)

## Document Information

| Property     | Value                   |
| ------------ | ----------------------- |
| Document     | 13-project-bootstrap.md |
| Product      | SupportBay              |
| Version      | v1                      |
| Status       | Approved                |
| Last Updated | June 2026               |

---

# Purpose

This document defines how SupportBay initializes, boots, and prepares the application during every WordPress request.

The bootstrap process should be deterministic, lightweight, and easy to extend.

Business logic must never exist inside the main plugin file.

---

# Bootstrap Philosophy

The main plugin file is only responsible for:

- Validating requirements
- Loading Composer
- Defining constants
- Starting the application

Everything else belongs to the application.

---

# Bootstrap Flow

```text
WordPress
        │
        ▼
supportbay.php
        │
        ▼
Requirement Check
        │
        ▼
Composer Autoloader
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
Configuration
        │
        ▼
Module Loader
        │
        ▼
Register Services
        │
        ▼
Initialize Modules
        │
        ▼
Register Hooks
        │
        ▼
Application Ready
```

---

# Main Plugin File

Responsibilities:

- Plugin header
- Prevent direct access
- Minimum PHP version check
- Minimum WordPress version check
- Load Composer autoloader
- Instantiate Application
- Start the application

The file should remain as small as possible.

Target size:

Less than 80 lines.

---

# Environment Validation

Before booting, verify:

- PHP version
- WordPress version
- Required PHP extensions (if any)
- Composer autoloader availability

If validation fails:

- Do not initialize the plugin.
- Display an administrator notice.
- Prevent fatal errors.

---

# Constants

Define global plugin constants during bootstrap.

Recommended constants:

```text
SBAY_VERSION
SBAY_FILE
SBAY_PATH
SBAY_URL
SBAY_BASENAME
SBAY_ASSETS_URL
SBAY_LANG_PATH
SBAY_MIN_PHP
SBAY_MIN_WP
```

Constants should only describe the plugin environment.

Avoid storing configuration values as constants.

---

# Composer

Composer is mandatory.

Responsibilities:

- PSR-4 autoloading
- Third-party packages
- Development tooling

Autoload file:

```text
vendor/autoload.php
```

---

# Application

The Application class is the entry point for SupportBay.

Responsibilities:

- Create the Kernel
- Store the Service Container
- Expose helper methods
- Start the application

Only one Application instance should exist.

---

# Kernel

The Kernel orchestrates the bootstrap process.

Responsibilities:

- Load configuration
- Register services
- Load modules
- Register hooks
- Initialize providers
- Dispatch startup events

The Kernel should not contain business logic.

---

# Service Container

The container is created before any module is loaded.

Responsibilities:

- Register services
- Resolve dependencies
- Manage singleton instances

Every domain should receive dependencies through the container.

---

# Configuration

Configuration loads before modules.

Sources may include:

- Plugin defaults
- WordPress options
- Environment overrides (future)

Configuration should be immutable during runtime unless explicitly updated.

---

# Module Loader

The Module Loader discovers and initializes all registered domains.

Example order:

1. Core
2. Database
3. Provider
4. Authentication
5. Customer
6. Ticket
7. Message
8. Attachment
9. Department
10. Notification
11. Activity
12. REST
13. Admin

Modules should register themselves through a common interface.

---

# Hook Registration

WordPress hooks are registered after services are available.

Examples:

- init
- admin_init
- rest_api_init
- wp_enqueue_scripts
- admin_enqueue_scripts
- cron events

Avoid registering hooks inside constructors whenever possible.

---

# Asset Registration

Assets should be registered during bootstrap but only enqueued when required.

Examples:

- Admin Dashboard
- Customer Portal
- Settings
- Reports

Unused assets must not load.

---

# REST API Registration

REST routes should register after:

- Services
- Authentication
- Permissions
- Providers

Controllers should not register themselves directly.

---

# Provider Initialization

Provider Manager initializes enabled providers.

Each provider validates:

- Configuration
- Credentials
- Availability

Misconfigured providers should not prevent the application from loading.

---

# Event Dispatch

Once boot is complete, dispatch an internal action.

Example:

```text
supportbay/booted
```

This allows modules and third-party extensions to perform post-boot initialization.

---

# Activation Flow

Plugin Activated

↓

Run Installer

↓

Create Database Tables

↓

Create Roles & Capabilities

↓

Store Plugin Version

↓

Schedule Cron Jobs

↓

Flush Rewrite Rules (if required)

↓

Ready

---

# Upgrade Flow

Plugin Updated

↓

Compare Stored Version

↓

Run Required Migrations

↓

Update Database Version

↓

Clear Relevant Caches

↓

Ready

---

# Deactivation Flow

Plugin Deactivated

↓

Unschedule Cron Jobs

↓

Clear Temporary Caches

↓

Keep User Data

---

# Uninstall Flow

Plugin Uninstalled

↓

Check "Delete Data" Setting

↓

Remove Database Tables (optional)

↓

Delete Options

↓

Delete Roles

↓

Delete Scheduled Events

↓

Delete Upload Directories (optional)

---

# Error Handling

Initialization failures should:

- Log the error
- Display an administrator notice (when appropriate)
- Fail gracefully

The plugin should never leave WordPress in a fatal state.

---

# Logging

During development, bootstrap events may be logged.

Examples:

- Application Started
- Kernel Loaded
- Modules Registered
- Providers Initialized

Production logging should remain minimal unless debugging is enabled.

---

# Testing

The bootstrap process should be independently testable.

Recommended tests:

- Environment validation
- Container creation
- Module registration
- Hook registration
- Provider loading

---

# Future Enhancements

Potential future improvements:

- Debug mode dashboard
- Bootstrap profiler
- Lazy-loaded modules
- Plugin dependency management
- Optional feature modules
- Background initialization

---

# Approved Decisions

✓ Minimal main plugin file.

✓ Application entry point.

✓ Kernel-based initialization.

✓ Composer required.

✓ PSR-4 autoloading.

✓ Environment validation.

✓ Centralized service container.

✓ Configuration loaded before modules.

✓ Module loader architecture.

✓ Deferred asset loading.

✓ Central REST registration.

✓ Provider initialization during boot.

✓ Lifecycle support for activation, upgrade, deactivation, and uninstall.

✓ Graceful error handling.

✓ Extensible post-boot event.

---
