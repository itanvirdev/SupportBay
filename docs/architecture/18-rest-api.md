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

## Saved Replies

Staff with `sbay_use_saved_replies` can list, search, and inspect saved replies through `GET /saved-replies` and `GET /saved-replies/{id}`. Active status is the safe list default. Managers with `sbay_manage_saved_replies` can create, update, deactivate, and delete records through the matching `POST`, `PUT`, and `DELETE` routes. All stored rich text passes through the shared reply sanitizer; the API never accepts executable HTML, embeds, tables, iframes, or custom CSS.

`POST /saved-replies/{id}/use` requires `sbay_use_saved_replies` and atomically records an insertion only while the reply is active. The response exposes aggregate `usage_count`, `last_used_at`, and `last_used_by` values. This metric represents insertion into a staff composer, not guaranteed message delivery or unchanged content.

`GET /saved-replies` accepts `orderby=title|usage|recent`. The service rejects all other values before the repository selects from its fixed SQL order clauses. Staff composer retrieval uses usage ordering; management interfaces may choose any supported order.

Saved replies may include an optional sanitized category. `GET /saved-replies` accepts an exact `category` filter, searches category text with titles and content, and returns the categories present in the permitted status collection as response metadata. Categories are labels owned by saved replies rather than a separate shared taxonomy.

Saved-reply list metadata advertises a fixed catalog of canonical `{{placeholder}}` tokens. The Settings editor uses this server-owned catalog for insertion controls. Staff ticket composers resolve only approved ticket, customer, assignment, department, and purchase context values and HTML-escape every replacement before passing the result to TinyMCE. Unknown or unavailable tokens remain visible.

Saved replies may set an optional `department_id` applicability scope. Composer list requests pass the current ticket department and receive global replies plus exact matches. Ordinary staff requests without a department receive global replies only. Managers may omit the scope to inspect every record in Settings. Department scope improves relevance and is not treated as a secret-data boundary.

## Customer Ticket Categories

Authenticated customers load active categories for a selected department through `GET /portal/categories?department_id={id}`. The result combines global categories with categories scoped to that department and never exposes inactive records.

`POST /portal/tickets` accepts `category_id`. When the selected department has applicable categories, the field is required. The portal service rejects inactive, missing, and cross-department selections before purchase entitlement resolution or ticket persistence. Departments without applicable categories continue to allow uncategorized tickets for backward compatibility.

Staff ticket context includes the ticket's category name and the active global or department-scoped categories applicable to that ticket. Staff with `sbay_change_ticket_category` may submit the `category` action to `POST /admin/tickets/{id}/actions`, using a category ID or an empty value to clear classification.

The ticket service validates category scope before persistence and emits the existing `TicketChanged` domain event. Category changes therefore produce dedicated `Category Changed` timeline activity. When a department move makes the current category invalid, the service clears it and records both department and category changes.

`GET /tickets` accepts `category_id={id}` for exact classification and `category_id=uncategorized` for tickets without a category. Queue rows include `category_id` and the joined safe `category_name`; historical tickets retain their relationship even if the category is later inactive.

The administrator ticket-options response includes active categories with their optional department scope. The shared queue uses those records for filtering and bulk controls. The `category` bulk action assigns or clears classification through `TicketService::changeCategory()` for every ticket, preserving normal events and returning compatible updates alongside per-ticket scope failures.

Category lifecycle routes require `sbay_manage_categories` for creation, mutation, and deletion. Deleting a category referenced by any ticket returns `409 CATEGORY_IN_USE`; administrators must deactivate it instead, preserving historical classification labels.

## Ticket Tags Foundation

Staff with ticket-view permission may list and inspect global tags through `GET /tags` and `GET /tags/{id}`. Managers with `sbay_manage_tags` may create, update, deactivate, and delete unused records through the matching versioned routes. In-use deletion returns `409 TAG_IN_USE`.

Ticket relationships are normalized through a unique many-to-many junction. Assignment routes, ticket context, queue filtering, bulk actions, activities, and Settings management are deferred to Ticket Tag Workflow Integration.

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

The React customer portal exposes `/support/login/` and `/support/register/` while WordPress remains the session and password authority. `POST /auth/login`, `POST /auth/register`, `GET /auth/session`, and `POST /auth/logout` use REST nonces and same-origin cookies. Registration follows the native `users_can_register` setting, creates a WordPress Subscriber, and links it to a SupportBay customer record. The legacy `sbay_customer` role is migrated to Subscriber and removed.

The public `/support/guest-ticket/` screen posts presales enquiries to
`POST /portal/guest-tickets`. The nonce-protected endpoint is controlled by the
guest-ticket setting and accepts first name, last name, email, subject, and
description, and one optional attachment. The attachment follows the General →
File size and extension policy. The flow assigns the default Support department and deliberately
does not accept a provider, category, purchase reference, verification, custom
fields, or attachments. Existing WordPress users are reused by email and their
submitted name is refreshed for non-staff accounts; otherwise a new Subscriber account and customer
record are created. Ticket-created notifications use the standard WordPress
mail-backed notification pipeline, and new accounts receive WordPress's native
password setup email.

`GET` and `PUT /settings/general` manage the protected SupportBay registration override. When enabled, customer registration is permitted even if WordPress `users_can_register` is disabled. When off, the effective state strictly follows WordPress. Both the portal bootstrap and registration endpoint resolve the same service-owned effective value.

Main settings also include an absolute registration-form disable flag, a guest-ticket creation policy flag, and the client default role. Administrators choose the default from roles currently registered in WordPress; the backend validates that the role still exists and falls back to Subscriber if it is removed. Subscriber is the recommended customer default. The registration disable flag takes precedence over both the SupportBay override and WordPress registration.

Custom ticket track IDs are optional. When enabled, the Tickets module retains its random uppercase hexadecimal identifier and formats it with the administrator-defined prefix and a 6–32 character random length. Ticket creation and ticket splitting share the same collision-checked generator. Random 8-character IDs remain the default, existing IDs are preserved, and `track_id` uses `VARCHAR(64)` to accommodate configured formats.

Activation creates or reuses a published WordPress page named Support, inserts `[supportbay]`, and saves that page as the initial Support Portal Page; there is no synthetic hard-coded portal URL. Administrators may select another published page. The selected page always renders the isolated SupportBay portal document. Shortcode mode is an independent option that enables `[supportbay]` on other WordPress pages through their active theme. Each entry point derives its portal URL, authentication redirects, client navigation, and virtual child-route rewrites from its own page permalink.

The selected portal page is marked through WordPress's native `display_post_states` filter as `SupportBay`. WordPress combines it with existing states using its standard comma-separated presentation, for example `Front Page, SupportBay`.

When the selected portal page is also the static Front Page, its permalink path is the site root. React preserves the empty base path instead of falling back to `/support`, and narrowly scoped root-level rewrites handle only SupportBay virtual routes such as login, tickets, purchases, and profile.

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

## Resolve Ticket

```http
POST /tickets/{id}/resolve
```

Requires the ticket-status capability. Only open, pending, or answered tickets can be resolved.

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

The built-in `support` department is created automatically, remains active, and cannot be deleted. Portal ticket creation may omit `department_id`; the service then assigns Support. The customer form hides its department selector when Support is the only active choice.

```http id="r26"
GET /departments
POST /departments
PUT /departments/{id}
DELETE /departments/{id}
```

---

# Notification Endpoints

## Manage Templates

```http
GET /sbay/v1/admin/notification-templates
GET /sbay/v1/admin/notification-templates/{event}/{recipient}
PUT /sbay/v1/admin/notification-templates/{event}/{recipient}
POST /sbay/v1/admin/notification-templates/{event}/{recipient}/reset
POST /sbay/v1/admin/notification-templates/{event}/{recipient}/preview
POST /sbay/v1/admin/notification-templates/{event}/{recipient}/test-email

GET /sbay/v1/admin/notification-preferences
PUT /sbay/v1/admin/notification-preferences
```

Requires:

```text
sbay_manage_settings
```

Templates are predefined by event and recipient type. Updates support status, subject, sanitized HTML content, and sanitized plain-text content.

Preview renders optional unsaved draft fields against server-owned sample data without persistence. Test email requires a valid `test_recipient`, sends rendered plain text through WordPress mail, and records the delivery attempt in notification logs.

---

## Get Logs

```http
GET /sbay/v1/admin/notifications
GET /sbay/v1/admin/notifications/{id}
POST /sbay/v1/admin/notifications/{id}/retry
```

Requires `sbay_manage_settings`. Listing supports search, status, event, channel, pagination, and safe ordering. Responses exclude stored message bodies, headers, payloads, and raw metadata. Retry updates the original record and remains subject to atomic claiming and the global three-attempt limit.

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
# Notification Retention

Administrators with the SupportBay settings capability can read and update the
notification retention policy through `GET|PUT /sbay/v1/admin/notification-retention`.
They can trigger one bounded cleanup batch through
`POST /sbay/v1/admin/notification-retention/cleanup`.

Cleanup is restricted to terminal records older than the configured cutoff.
Pending, processing, and retry-eligible failed deliveries are not deleted.

# Notification Delivery Reports

Managers and administrators with `sbay_view_reports` can request aggregate
delivery metrics through `GET /sbay/v1/reports/notifications`.

Supported filters are `date_from`, `date_to`, `channel`, and `event`. Date
ranges use `Y-m-d`, must be ordered, and are limited to 367 days. The response
contains summary counters and rates plus daily, event, and channel breakdowns.
It intentionally excludes recipient, subject, content, payload, headers, and
metadata.

# Ticket Performance Reports

Managers and administrators with `sbay_view_reports` can request ticket
performance metrics through `GET /sbay/v1/reports/tickets`.

Supported filters are `date_from`, `date_to`, `department_id`, `category_id`,
`tag_id`, `assigned_agent_id`, and `priority`. `category_id=uncategorized` selects tickets
without classification. The endpoint returns aggregate ticket,
staff-response, need-reply, resolved, closed, and average first-response values,
plus daily, department, category, and agent breakdowns. Category groups retain
historical names for inactive records and label null relationships as
`Uncategorized`. Tag workload preserves historical tag names, labels tickets without tags as `Untagged`, and counts a multi-tag ticket in every applicable tag row while summary totals remain unique. Date ranges are limited to 367
days, and trashed tickets are excluded.

# Report Exports

Administrators with `sbay_export_reports` can download the currently filtered
reports through:

- `GET /sbay/v1/reports/tickets/export`
- `GET /sbay/v1/reports/notifications/export`

Export routes accept the same query parameters and validation limits as their
JSON report routes. They stream UTF-8 CSV attachments with deterministic,
date-based filenames. CSV fields are escaped with native CSV rules, and values
that could be interpreted as spreadsheet formulas are prefixed safely.

# Ticket SLA Policy

Administrators manage calendar-time first-response targets through
`GET|PUT /sbay/v1/admin/ticket-sla-policy`. The policy stores one target in
minutes for every ticket priority and validates values between 15 and 10080.

Ticket report responses include the active policy, within-target, breached,
and awaiting-within-target totals plus factual first-response bands. This
version does not implement business-hour calendars, automatic escalation, or
historical SLA policy snapshots.

Ticket queue responses include server-calculated `sla_state`,
`sla_target_minutes`, `sla_due_at`, and `sla_remaining_minutes`. The ticket
list accepts `sla_state` and supports `orderby=sla_due`. An unanswered ticket
is `due_soon` after 75 percent of its target has elapsed and `breached` after
the target passes. Answered tickets are classified as `met` or `breached`
against their actual first-response timestamp. A disabled policy returns the
`disabled` state.

First-response breaches are detected asynchronously every five minutes in
batches of 20. A dedicated ticket-owned table atomically records each
ticket-and-metric pair before the `TicketSlaBreached` domain event is
dispatched. The initial listener creates a system-authored ticket timeline
activity. Notification and escalation side effects remain separate listeners.

# Ticket Tag Workflow

Staff ticket lists accept `tag_id` and include safe assigned-tag metadata in each queue row. Active tag choices are returned by `GET /sbay/v1/admin/tickets/options` and ticket context responses include both current and available tags.

`POST /sbay/v1/admin/tickets/{id}/actions` supports `tag_add` and `tag_remove`. The same actions are accepted by `POST /sbay/v1/admin/tickets/bulk-actions` for up to 100 tickets. Ticket mutations require `sbay_change_ticket_tags`, while tag-record administration uses `sbay_manage_tags`; reads continue to use the normal ticket-view capability.

The WordPress administrator bootstrap exposes only the `canManageTags` boolean, never role assumptions. Settings uses it to reveal the React Tags workspace, which consumes the protected tag CRUD routes for search, lifecycle editing, color management, and safe deletion feedback.

# Ticket Custom Fields Foundation

`GET /sbay/v1/custom-fields` and `GET /sbay/v1/custom-fields/{id}` require ticket-view permission. Definition creation, updates, and deletion require `sbay_manage_custom_fields` through the matching `POST`, `PUT`, and `DELETE` routes.

Definitions support text, textarea, number, select, checkbox, date, email, and URL types; optional department scope; required and customer-visible flags; status; choices; and sort order. Ticket values are normalized through the service layer and stored separately. Value-facing REST routes and React forms are deferred to workflow milestones.

The administrator bootstrap exposes `canManageCustomFields` from the dedicated WordPress capability. Settings uses that flag to reveal definition management, including type and scope filters, select-choice editing, customer-visibility controls, lifecycle changes, and safe deletion feedback.

# Customer Custom Field Ticket Creation

`GET /sbay/v1/portal/custom-fields?department_id={id}` returns only active, customer-visible definitions applicable to the selected department. The response contains safe rendering metadata and does not expose lifecycle or internal visibility controls.

`POST /sbay/v1/portal/tickets` accepts `custom_fields` as a field-ID-to-value map. `CustomFieldService` rejects unknown, inactive, private, or cross-department IDs and normalizes every supported type. Required fields are checked before entitlement resolution and ticket persistence. Validated values are attached to the new ticket, and a persistence failure rolls back the opening message, any saved custom values, and the ticket.

# Staff Custom Field Ticket Workflow

`GET /sbay/v1/admin/tickets/{id}/context` includes active definitions applicable to the ticket and stored historical values whose definitions are now inactive. Each item contains safe rendering metadata, its current value, and active/required state.

Staff submit the `custom_field` action to `POST /sbay/v1/admin/tickets/{id}/actions` with a `field_id` and value. The route requires `sbay_change_ticket_custom_fields`; agents, managers, and administrators receive this capability. The controller delegates validation and persistence to `CustomFieldService`. Empty optional values clear storage, while required, inactive, invalid, and cross-department mutations return a safe validation error.

# Custom Field Reporting Foundation

`GET /sbay/v1/reports/tickets` and its CSV export accept `custom_field_id` and an optional `custom_field_value`. A field without a value selects tickets having any stored value for that definition; supplying a value performs an exact normalized match. A value without a field is rejected.

The report response echoes the applied filters and returns `custom_fields`, a workload breakdown for the selected definition only. Each row includes tickets, staff responses, need-reply tickets, and resolved-or-closed tickets. `GET /sbay/v1/admin/tickets/options` supplies active definition names, types, choices, and department scope for type-aware report controls. CSV export includes the identical selected-field workload.

# Customer Custom Field Detail Visibility

`GET /sbay/v1/portal/tickets/{id}` includes `custom_fields` after the normal current-customer ownership check. Only stored values backed by a currently customer-visible definition are returned. Staff-only definitions are excluded entirely, including their labels.

Each item contains only the definition ID, display name, type, and normalized stored value. Definition choices, required/lifecycle flags, department scope, updater identity, and persistence timestamps remain private. The portal renders these values read-only; customers continue to edit them only by creating a new ticket with the applicable creation form.

# Custom Field Queue Filtering

`GET /sbay/v1/tickets` accepts `custom_field_id` and optional `custom_field_value` for authorized staff. Selecting only a field returns tickets having any stored value for that definition. Supplying a value performs an exact match against its normalized stored representation.

Filtering uses a correlated `EXISTS` subquery, preserving one result and one count per ticket. The administrator options endpoint supplies active field names, types, choices, and department scope for type-aware filters. These controls render only in staff mode; customer queue requests and list responses do not expose custom-field metadata or values.

# Custom Field Bulk Update Workflow

`POST /sbay/v1/admin/tickets/bulk-actions` accepts the `custom_field` action with a structured `value` containing `field_id` and `value`. Requests require `sbay_change_ticket_custom_fields` and remain bounded to 100 unique positive ticket IDs.

The controller delegates each selected ticket to `CustomFieldService::setValue()`. Type, choice, required-state, active-state, and department-scope rules therefore remain identical to individual edits. Compatible tickets succeed while invalid tickets return keyed failure messages; successful changes retain their normal actor-attributed, value-safe audit events. Empty values clear optional fields.

# First Reply Ownership and Agent Handoff

The first public reply from an agent or manager assigns an unassigned ticket to that responder. Customer replies and internal notes never claim ownership, and an existing assignee is never replaced by this automation.

Agents, managers, and administrators may assign, unassign, or transfer an individual ticket from its detail action endpoint. Assignment targets must be existing WordPress users with SupportBay ticket-view permission. Bulk assignment remains restricted to managers and administrators through `sbay_reassign_ticket`. Every actual ownership change continues through `TicketService::changeAssignment()`, preserving activity and assignment/reassignment notification behavior.

# Ticket Queue Refresh and Need Reply Sorting

Ticket-list and ticket-detail auto-refresh is enabled by default at a 60-second interval. Administrators can disable it or select an interval from 5 to 3,600 seconds in General settings. Background refresh pauses for hidden tabs and active mutations so it does not interrupt selections, submissions, or unsaved editor content.

Need Reply smart sorting is enabled by default. The setting exposes the Need Reply switch in the staff queue; when active, the service asks the repository to prioritize the ticket whose latest unanswered customer or guest reply has waited longest. Disabling the setting removes the switch from the staff workspace and preserves the queue's explicitly selected ordering.

# Dashboard and Portal Logos

General settings provides a dedicated Logo tab with independent Dashboard and Portal logo attachment IDs. Selection and upload use WordPress's native media library; the settings service accepts only valid image attachments. Removing a selection restores the bundled `assets/images/supportbay-logo.svg` fallback without deleting the WordPress media item.

The Dashboard logo renders in the PHP-owned SupportBay administration header. The Portal logo is exposed through the safe portal bootstrap and renders in both the authenticated customer navigation and customer authentication screens. Logo changes use the tab-level Save Changes and Discard workflow.

# Customer File Policy

The General File tab controls whether customers may attach files, the per-file limit from 1–100 MB, allowed extension groups, and photo/PDF popup previews. Photos are the only default selected group. The portal bootstrap uses this policy to render the picker, its `accept` list, and size guidance.

The server remains authoritative. Customer uploads pass the enabled flag, configured byte limit, and normalized extension allowlist through `AttachmentService`; agent uploads retain the broader internal attachment policy. Supported groups cover photos, videos, audio, office documents, text, CSV, PDF, ZIP, JSON, STL models, and DICOM files. When preview is enabled, customer-visible images and PDFs open in a protected blob-backed portal viewer after the existing ownership-authorized download request.

# Ticket Status Display Labels

The General Status tab allows administrators to rename the display labels for Open, Pending, Answered, Resolved, and Closed. Empty or missing labels fall back to their defaults. Canonical enum and database values remain unchanged, preserving API filters, transitions, events, metrics, and integrations.

The sanitized label map is exposed through both authenticated UI bootstraps and is used by staff queues, the Closed filter, staff ticket details, customer ticket details, and the portal dashboard. Settings use the same tab-level Save Changes and Discard workflow.

# User Role Settings

`GET /sbay/v1/roles` returns the protected Administrator summary, SupportBay support roles, user counts, lifecycle metadata, selected capabilities, and a categorized server-owned capability catalog. `POST /roles`, `PUT /roles/{slug}`, and `DELETE /roles/{slug}` require `sbay_manage_roles`.

The API accepts only catalogued SupportBay capabilities. Administrator cannot be mutated or deleted, and any SupportBay role assigned to WordPress users is deletion-protected. New slugs are generated server-side from role names and remain immutable. Inactive roles retain metadata but lose dashboard permissions and are hidden from native WordPress role assignment. Team-member creation remains in WordPress Users.
