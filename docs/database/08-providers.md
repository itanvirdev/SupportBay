# SupportBay – Database Specification

## Table: `wp_sbay_providers`

---

# Purpose

Stores configuration and status information for external service providers integrated with SupportBay.

A provider represents an external system that extends SupportBay functionality, such as marketplaces, AI services, notification services, or future integrations.

The Provider Registry allows integrations to be enabled, configured, and managed without modifying the core plugin.

---

# Relationships

```text
SupportBay
     │
     ▼
Providers
     │
     ├── Marketplace
     ├── AI
     ├── Notification
     └── Future Integrations
```

Each provider has its own configuration and lifecycle.

---

# Table Structure

| Column            | Type            | Null | Default           | Index   | Description                |
| ----------------- | --------------- | ---- | ----------------- | ------- | -------------------------- |
| id                | BIGINT UNSIGNED | No   | AUTO_INCREMENT    | PRIMARY | Provider ID                |
| slug              | VARCHAR(100)    | No   | -                 | UNIQUE  | Unique provider identifier |
| name              | VARCHAR(150)    | No   | -                 | -       | Display name               |
| category          | VARCHAR(50)     | No   | marketplace       | INDEX   | Provider category          |
| version           | VARCHAR(30)     | Yes  | NULL              | -       | Provider version           |
| is_enabled        | TINYINT(1)      | No   | 0                 | INDEX   | Enable/disable provider    |
| settings          | LONGTEXT        | Yes  | NULL              | -       | JSON configuration         |
| last_connected_at | DATETIME        | Yes  | NULL              | INDEX   | Last successful connection |
| last_error        | TEXT            | Yes  | NULL              | -       | Last connection error      |
| metadata          | LONGTEXT        | Yes  | NULL              | -       | Provider metadata          |
| created_at        | DATETIME        | No   | CURRENT_TIMESTAMP | INDEX   | Creation timestamp         |
| updated_at        | DATETIME        | No   | CURRENT_TIMESTAMP | INDEX   | Last updated               |

---

# Categories

Supported categories:

```text
marketplace

ai

notification

payment

storage

other
```

---

# Version 1 Providers

```text
envato
```

Disabled by default:

```text
edd

woocommerce

freemius

paddle

gumroad

lemonsqueezy
```

---

# Future Providers

Possible future integrations:

### AI

```text
openai
gemini
claude
```

### Notifications

```text
smtp
slack
discord
telegram
```

### Storage

```text
amazon_s3
cloudflare_r2
digitalocean_spaces
```

---

# Provider Settings

Each provider stores its own JSON configuration.

Example:

```json
{
	"client_id": "...",
	"client_secret": "...",
	"enabled": true
}
```

SupportBay does not enforce a provider-specific schema.

Each Provider Service validates its own configuration.

---

# Connection Status

Connection state is determined by:

- Configuration validity
- Authentication success
- API availability

SupportBay may periodically refresh provider status.

---

# Business Rules

- Every provider has a unique slug.
- Providers may be enabled or disabled.
- Disabled providers are ignored by the system.
- Provider configuration belongs to the provider itself.
- Provider settings should never be hard-coded.
- Secrets should always be encrypted before storage (future enhancement).

---

# Repository Responsibilities

The Provider Repository is responsible for:

- Loading providers
- Saving configuration
- Enabling/disabling providers
- Retrieving provider settings
- Connection status updates

Provider-specific API logic belongs to individual Provider Services.

---

# Future Enhancements

Potential future features:

- OAuth management
- Automatic provider discovery
- Health monitoring
- Background synchronization
- Secret encryption
- Connection diagnostics
- Provider marketplace

---

# Approved Decisions

✓ Provider registry

✓ Provider-independent configuration

✓ JSON-based settings

✓ Enable/disable support

✓ Category-based organization

✓ Future-ready integration architecture

✓ Repository-driven management

---
