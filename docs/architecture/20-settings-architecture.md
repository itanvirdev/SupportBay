# SupportBay – Settings Architecture

---

# Purpose

The Settings Architecture defines how SupportBay manages all configurable system behavior from the WordPress admin dashboard.

It provides a centralized, structured, and schema-driven configuration system for:

- Core plugin settings
- Provider integrations
- Notifications & email templates
- Ticket system behavior
- Attachment rules
- Role & permission overrides
- UI behavior

---

# Core Principle

Settings must be:

> Schema-driven, modular, and extensible — not hard-coded.

---

# Settings Architecture Overview

```text id="s1"
WordPress Admin UI (React)
        ↓
Settings API Layer (REST)
        ↓
Settings Service (PHP)
        ↓
Settings Repository
        ↓
wp_options (or structured tables)
```

---

# Settings Storage Strategy

All settings are stored using a **hybrid model**:

## 1. wp_options (Primary)

Used for:

- global settings
- module configs
- provider configs
- feature toggles

---

## 2. Structured JSON per module

Example:

```text id="s2"
option_name: sbay_settings_tickets
option_value: JSON
```

---

# Settings Categories

SupportBay settings are grouped into modules:

---

## 1. Core Settings

- plugin general config
- system behavior
- performance settings

---

## 2. Ticket Settings

- default status
- priority rules
- auto assignment
- reopen rules
- department enforcement

---

## 3. Notification Settings

- email templates
- SMS settings
- Slack integration
- notification toggles

---

## 4. Provider Settings

- Envato config
- API keys
- OAuth settings
- connection status

---

## 5. Attachment Settings

- file upload rules
- max size
- allowed file types
- security settings
- public/private access rules

---

## 6. Role & Permission Settings

- capability mapping
- custom roles
- agent/manager permissions
- UI access control

---

## 7. UI Settings

- theme mode (light/dark)
- dashboard layout
- ticket UI preferences
- rich text editor toggle

---

# Settings Schema System

Each module defines a **settings schema**

---

## Example Schema

```php id="s3"
return [
    'ticket_auto_assign' => [
        'type' => 'boolean',
        'default' => true,
        'label' => 'Auto Assign Tickets',
    ],
    'default_priority' => [
        'type' => 'string',
        'default' => 'normal',
        'options' => ['low', 'normal', 'high'],
    ],
];
```

---

# Why Schema Matters

It enables:

- automatic React form generation
- validation rules
- consistent API responses
- dynamic admin UI
- plugin extensibility

---

# Settings Service Layer

Core service:

```text id="s4"
SupportBay\Services\SettingsService
```

Responsibilities:

- get settings
- update settings
- validate schema
- cache settings
- merge defaults

---

## Example Usage

```php id="s5"
Settings::get('ticket_auto_assign');
Settings::set('default_priority', 'high');
```

---

# REST API for Settings

## Get Settings

```http id="s6"
GET /settings/{module}
```

---

## Update Settings

```http id="s7"
POST /settings/{module}
```

---

## Response Example

```json id="s8"
{
	"success": true,
	"data": {
		"ticket_auto_assign": true,
		"default_priority": "normal"
	}
}
```

---

# React Settings UI

Settings UI is fully dynamic:

```text id="s9"
SettingsPage
   ↓
SettingsSchemaLoader
   ↓
DynamicFormRenderer
```

---

## Form Types Supported

- text
- textarea
- number
- boolean toggle
- select dropdown
- multi-select
- file upload
- color picker
- JSON editor

---

# Provider Settings System

Each provider uses:

```text id="s10"
settings: JSON
```

But UI is generated from schema:

- Envato OAuth fields
- API keys
- webhook URLs

---

# Email Template System

Stored under Notification Settings:

```text id="s11"
ticket_created
ticket_reply
ticket_closed
```

Each template supports:

- subject
- body (HTML)
- placeholders

---

## Example Placeholders

```text id="s12"
{{customer_name}}
{{ticket_id}}
{{ticket_subject}}
{{support_url}}
```

---

# Attachment Settings

Controls:

- max file size
- allowed types
- image preview
- public/private rules

Example:

```text id="s13"
jpg, png, pdf, zip, mp4
```

---

# Role & Capability Settings

Allows admin to:

- edit agent permissions
- define manager access
- create custom roles
- override defaults

Stored as:

```text id="s14"
sbay_role_capabilities
```

---

# Caching Strategy

Settings are cached in:

- memory (request level)
- wp_cache (optional)
- transient cache (future optimization)

---

# Validation System

Each setting is validated using schema rules:

- type checking
- allowed values
- required fields
- sanitization

---

# Security Rules

- Only admin/manager can update settings
- Sensitive data encrypted (future enhancement)
- No raw secrets exposed in frontend
- Provider credentials protected

---

# Extensibility

Developers can extend settings via:

```php id="s15"
add_filter('sbay_settings_schema', function($schema) {
    return $schema;
});
```

---

# Settings UI Flow

```text id="s16"
Admin opens Settings
        ↓
React loads module schema
        ↓
Dynamic form generated
        ↓
User updates values
        ↓
REST API saves settings
        ↓
Service validates + stores
```

---

# Module-Based Settings Isolation

Each module owns its settings:

```text id="s17"
Tickets → ticket settings
Auth → auth settings
Providers → provider settings
Notifications → email settings
```

No global pollution.

---

# Future Enhancements

- Settings versioning
- Import/Export config
- Multi-site settings sync
- AI-assisted setting recommendations
- Audit log for setting changes
- Role-based settings visibility

---

# Approved Decisions

✓ Schema-driven settings system

✓ Modular settings per feature

✓ React dynamic form generation

✓ REST-based configuration API

✓ wp_options + JSON hybrid storage

✓ Secure provider configuration handling

✓ Extensible via filters/hooks

✓ Central SettingsService layer

---
