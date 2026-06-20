# SupportBay – Service Container Architecture

---

# Purpose

The Service Container is the central Dependency Injection (DI) system of SupportBay.

It is responsible for:

- Registering services
- Resolving dependencies
- Managing singleton instances
- Bootstrapping core modules
- Providing a structured runtime for all plugin features

It ensures SupportBay remains modular, testable, and scalable.

---

# Core Concept

Instead of creating classes manually:

```php
new TicketService();
```

We resolve everything through the container:

```php
app('ticket')->create();
```

or:

```php
Container::get(TicketService::class);
```

---

# Container Responsibilities

The container handles:

- Service registration
- Dependency resolution
- Singleton lifecycle
- Factory bindings
- Boot sequence execution

It does NOT handle business logic.

---

# Architecture Overview

```text id="c1"
Plugin Bootstrap
        ↓
Service Container
        ↓
Service Providers
        ↓
Core Services
        ↓
Repositories
        ↓
External Providers (Envato, etc.)
        ↓
REST API + React Layer
```

---

# Container Implementation Strategy

SupportBay uses a lightweight custom container.

No external framework dependency.

---

## Core Class

```php id="c2"
SupportBay\Core\Container
```

---

## Responsibilities

- bind()
- singleton()
- make()
- has()
- resolve()

---

# Service Registration Types

## 1. Singleton Services

Used for:

- TicketService
- AuthService
- NotificationService

```php id="c3"
Container::singleton(TicketService::class);
```

Same instance reused throughout request lifecycle.

---

## 2. Transient Services

Used for lightweight objects:

- DTOs
- Builders
- Temporary processors

```php id="c4"
Container::bind(TicketFilter::class);
```

---

## 3. Factory Bindings

Used when instantiation needs logic:

```php id="c5"
Container::bind(EnvatoClient::class, function () {
    return new EnvatoClient(
        get_option('sbay_envato_token')
    );
});
```

---

# Service Provider System

Instead of registering everything in one place, SupportBay uses providers.

Example:

```text id="c6"
CoreServiceProvider
TicketServiceProvider
ProviderServiceProvider
NotificationServiceProvider
```

Each provider is responsible for registering a module.

---

## Example Provider

```php id="c7"
class TicketServiceProvider
{
    public function register(Container $container)
    {
        $container->singleton(TicketService::class);
        $container->singleton(MessageService::class);
    }
}
```

---

# Boot Process

```text id="c8"
1. Plugin loads
2. Container is created
3. Core providers registered
4. Providers booted
5. Services resolved
6. REST API initialized
7. React app loaded
```

---

# Lifecycle Hooks

Each service provider can define:

## register()

Used to bind services.

## boot()

Used after all services are registered.

```php id="c9"
public function boot(Container $container)
{
    // Run after dependencies are available
}
```

---

# Global Helper (Optional)

For simplicity:

```php id="c10"
function sbay() {
    return Container::getInstance();
}
```

Usage:

```php id="c11"
sbay()->get(TicketService::class);
```

---

# Service Categories in SupportBay

## Core Services

- TicketService
- MessageService
- DepartmentService
- ActivityService

## Authentication Services

- AuthService
- TokenService

## Integration Services

- EnvatoService
- ProviderManager

## Notification Services

- NotificationService
- MailerService

## Support Services

- FileUploadService
- ValidationService

---

# Dependency Flow

```text id="c12"
Controller (REST API)
        ↓
Service Layer
        ↓
Repository Layer
        ↓
Database Layer
```

Services NEVER talk directly to WordPress DB.

---

# Anti-Pattern Rules

❌ No service should directly call:

```php
wpdb
```

❌ No business logic in:

- REST controllers
- React API handlers
- Providers

✔ Everything goes through services

---

# Service Resolution Rules

- Services must be stateless where possible
- Heavy services should be singleton
- Repositories are always injected
- WordPress functions are wrapped in helpers/services

---

# Example Real Usage

```php id="c13"
$ticketService = Container::get(TicketService::class);

$ticketService->create([
    'subject' => 'Login issue',
    'department_id' => 1
]);
```

---

# WordPress Integration Layer

SupportBay container integrates with WordPress:

- init hook → boot services
- plugins_loaded → register providers
- rest_api_init → load API services

---

# Error Handling

- Missing service → throw ContainerException
- Circular dependency → detected and blocked
- Invalid binding → logged in debug mode

---

# Performance Strategy

- Lazy loading services
- Singleton caching per request
- Minimal reflection usage
- Pre-registered service map (future optimization)

---

# Future Enhancements

- Auto-discovery of providers
- Attribute-based dependency injection (PHP 8+)
- Caching compiled container map
- Plugin module isolation
- Service overrides (for extensions)

---

# Approved Design Decisions

✓ Custom lightweight DI container

✓ Service Provider architecture

✓ Singleton + factory support

✓ WordPress-compatible lifecycle

✓ Strict service layering (Controller → Service → Repository)

✓ No direct DB access outside repositories

✓ Scalable module-based service system

---
