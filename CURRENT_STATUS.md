# CURRENT_STATUS.md

# SupportBay Current Development Status

Last Updated

August 2026

### Conditional Ticket Taxonomy UI

- Customer ticket conversations visually distinguish replies from the support team.
- Customer and staff ticket lists omit the default Support department and uncategorized metadata when no additional taxonomy has been configured.
- Staff department, category, tag, and custom-field filters and bulk groups appear only when meaningful options exist.
- Customer ticket lists show reply counts and an Agent Replied badge while the latest public reply is from an agent or manager; a customer follow-up clears the state.
- Staff ticket lists and details show the ticket customer's actual WordPress avatar while customer list rows omit avatars; both queues show a sanitized latest-reply excerpt.

Current Version

v0.2.36 Prerelease

Prerelease policy: increment the patch version for every completed change set.

- Ticket workspace queue tabs, table rows, pagination, and shared view types are isolated as reusable partial components while the parent retains request and filter orchestration.
- Envato OAuth code and refresh exchanges use form-encoded client credentials without an empty bearer header, preventing duplicate client-authentication failures.
- Envato response handling accepts JSON or form-encoded OAuth responses and reports bounded, credential-safe HTTP diagnostics for unexpected provider responses.
- Envato token exchanges POST form-encoded parameters on the documented `/token` query string with an empty body, while bearer authentication remains omitted.
- Envato OAuth settings now distinguish the Secret Application Key from Personal Tokens, rejected client credentials return actionable recovery guidance, and a mocked request contract verifies that every token-exchange credential is transmitted once and unchanged.
- Configured Envato secrets now display a non-sensitive password mask, safely preserve the encrypted value during unrelated saves, and clearly distinguish a fresh login from retrying a single-use OAuth callback.
- Envato OAuth now follows the proven WordPress integration flow: credentials are sent once in a native form-body POST without `redirect_uri`, and the normalized customer identity is assembled from Envato's dedicated account, email, and username endpoints.
- Envato Marketplace-only account responses now receive a clear activation instruction while the portal keeps Support Genix's shared OAuth behavior: Login with Envato and Register with Envato use the same provider authorization URL with context-specific labels.
- Envato OAuth now follows Support Genix's email-first login behavior: a verified Envato email can authenticate or create the WordPress customer even when optional Marketplace account and username enrichment are unavailable.
- Envato OAuth profile lookups now use Support Genix's Authorization-only, 120-second WordPress request profile, with an endpoint-specific safe error if Envato cannot return the required email identity.
- Envato email-identity failures take precedence over generic Marketplace messaging, preserving the exact safe diagnostic required for live troubleshooting.
- Envato authorization cancellation now mirrors the expected portal behavior by returning the customer safely to the SupportBay portal instead of rendering an OAuth error.
- Envato authorization now matches Support Genix's exact public parameter set: no unsupported `scope` or `state` query parameter, no internal `/interaction` redirect, and callback protection retained through a short-lived HttpOnly verifier cookie.
- Removed the obsolete Envato interaction-route recovery path; provider API failures are now preserved safely instead of being misclassified and redirected to an invalid internal Envato endpoint.
- Administrators can select any available WordPress role as the Client User Default Role, and authenticated Support Managers/Agents now enter their capability-controlled SupportBay staff workspace instead of the customer-only portal.
- Support Agents, Support Managers, and Administrators can now use the SupportBay front-end staff ticket workspace with the shared queue, filters, bulk actions, rich reply/internal-note composer, and staff ticket-details sidebar used by the dashboard.
- Lemon Squeezy license-key verification and configurable support expiry are ready for live validation.

Current Branch

main

---

### Performance and Packaging

- Administrator Reports and Settings load as content-hashed asynchronous chunks.
- Customer authentication, dashboard, ticket, purchase, and profile routes load
  their React components on demand.
- The initial administrator JavaScript decreased from 341 KB to 241 KB, and the
  customer entry decreased from 237 KB to 197 KB.
- WordPress editor/media dependencies are limited to the SupportBay screens that
  use them.
- Added a deterministic production release builder with optimized, production-only
  Composer autoloading and a single installable plugin root.
- Production packages exclude development dependencies, React source, flow tests,
  AI instructions, internal documentation, tools, and local metadata.
- The audited ZIP decreased from 9.3 MB compressed / 46 MB unpacked to about
  525 KB compressed / 1.7 MB unpacked before the final Core Testing exclusion.

---

### Error and Empty-State Audit

- Added a reusable request-state component with accessible messaging, retry, and
  optional recovery actions.
- Initial request failures no longer leave ticket details, customer profiles,
  directories, reports, integrations, or audited settings on endless preloaders.
- Ticket, customer, and verification lists distinguish an empty installation
  from a filtered query with no matches.
- Filtered empty states provide a direct reset action, while customer ticket
  queues can lead directly to ticket creation.
- Background ticket refresh failures preserve already-loaded conversations and
  remain non-disruptive to active work.
- React administrator and portal flow tests now enforce the recoverable error
  and actionable empty-state contracts.
- The production React build and all 38 database-backed flow tests pass.

---

### Complete End-to-End Flow Testing

- Added a CLI-only WordPress flow runner with reliable process exit codes.
- The complete MVP suite now executes all 38 active flow-test workflows.
- Existing flows were isolated from persistent local data and corrected to use
  valid domain fixtures, ownership state, timestamps, and integration setup.
- Added dedicated security-authorization and installation-lifecycle flows.
- Fixed role activation defaults and explicit ticket timestamp persistence found
  by the end-to-end suite.
- The full database-backed suite, PHP syntax validation, distribution build, and
  whitespace validation pass cleanly.
- Notification delivery-log retention and Ticket SLA flows remain outside the
  MVP suite because those features were intentionally removed from MVP scope.

---

# Current Sprint

Security and REST Authorization Audit

Current Objective

Harden public authentication, ticket authorization, customer privacy, abuse
protection, and attachment storage for MVP production readiness.

Completed

- Public registration is locked to the WordPress Subscriber role.
- Anonymous guest submissions no longer mutate existing WordPress profiles.
- A centralized ticket access policy limits agents to owned and permitted
  unassigned tickets while managers and administrators retain full queue access.
- Customer email visibility requires a dedicated SupportBay capability.
- reCAPTCHA v3 runs in React login, registration, and guest-ticket forms and is
  verified server-side for action, score, and hostname.
- New attachments are stored outside the public WordPress document root and
  remain available only through authorized REST streaming.
- Installation lifecycle hardening restricts development tests, merges new
  defaults safely, supports ordered upgrades, prevents portal-page takeover,
  clears every scheduled worker, and provides explicit opt-in uninstall cleanup.
- SupportBay v1 is explicitly single-site only; multisite activation is blocked.

---

# Overall Progress

Foundation

```
100%
```

Business Modules

```
95%
```

Integration Foundation

```
100%
```

Verification Module

```
100%
```

Envato Provider

```
70%
```

Customer Features

```
10%
```

Admin Features

```
5%
```

Overall

```
≈70%
```

---

# Completed Foundation

The following architecture is considered stable.

Core

```
✅ Container

✅ Foundation

✅ Database

✅ Repository Base

✅ Entity Base

✅ Events

✅ Event Dispatcher

✅ Event Listeners

✅ Testing Framework

✅ Flow Tests

✅ Migration Registry

✅ Service Provider Registry
```

---

# Completed Modules

The following modules have passing Flow Tests.

```
✅ Tickets

✅ Messages

✅ Departments

✅ Activities

✅ Attachments

✅ Customers

✅ Authentication

✅ Providers

✅ Verifications

✅ Notification Foundation
```

Unless a bug exists,

these modules should not be refactored.

---

# Current Architecture

Core

↓

Modules

↓

Integrations

↓

Providers

Business modules remain provider-independent.

Providers adapt external APIs.

---

# Current Integration Architecture

Completed

```
IntegrationProvider

PurchaseVerificationProvider

IntegrationManager

IntegrationRegistry

IntegrationDiscovery

PurchaseVerificationData
```

Concrete Providers

```
Envato
```

Future

```
EDD

WooCommerce

Freemius

Paddle

LemonSqueezy
```

---

# Current Verification Architecture

Completed

```
PurchaseVerificationSchema

Verification Entity

VerificationRepository

VerificationService

Verification Events

VerificationServiceProvider

VerificationFlowTest
```

Business Rules

One Verification

↓

Many Tickets

One Ticket

↓

One Verification

Verification remains provider-independent.

---

# Envato Provider Status

Completed

```
EnvatoProvider

EnvatoServiceProvider

EnvatoApiClient

EnvatoOAuthService

EnvatoPurchaseService

EnvatoCustomerService

OAuthRoutes

ProviderConfiguration

EnvatoCustomer

EnvatoPurchase

EnvatoToken

README

MANUAL_TESTING
```

Completed

```
Refresh Token Consumption

Encrypted Refreshed Token Persistence

Reconnect-required Failure Handling

Provider Availability Filtering
```

---

# Current Task

Completed

```
PurchaseVerificationProvider

↓

EnvatoProvider

↓

PurchaseVerificationData

↓

VerificationService

↓

Verification Repository

ProviderVerificationFlowTest
```

Current Result

VerificationService verifies purchases through registered integrations without knowing concrete provider classes.

---

# Completed Portal Authentication Experience

```
Unauthenticated Portal Entry

↓

WordPress Login Handoff

↓

Single-use Magic Login

↓

OAuth Portal Redirect

↓

Portal Logout

↓

Auth and React Portal Flow Tests
```

---

# Completed Email Notification Foundation

```
TicketCreated / MessageCreated Events

↓

Notification Listeners

↓

NotificationService

↓

NotificationChannel Contract

↓

WordPress Email Channel

↓

Notification Delivery Log

↓

NotificationFlowTest
```

Current behavior

- Ticket creation emails the administrator and customer.
- Customer follow-up replies email the administrator.
- Agent replies email the customer.
- Initial ticket content and internal notes do not create duplicate or private emails.
- Successful and failed attempts create immutable audit records.
- Invalid recipients are recorded as retryable failures without channel delivery.
- Failed records can be replayed from their stored payload through the matching channel.
- Retries update the original audit record and stop after three attempts.
- Administrators can search and filter notification delivery logs through protected REST endpoints.
- Administrators can inspect delivery diagnostics and manually retry eligible failures.
- API responses never expose stored message bodies, headers, or raw metadata.
- Failed deliveries are scheduled automatically with 5, 10, and 20 minute retry delays.
- A self-healing WordPress Cron worker processes at most 20 due records per run.
- Cron and manual retries share the same atomic claim and three-attempt enforcement.
- Successful or exhausted deliveries have no remaining retry schedule.
- Ticket and public-reply listeners persist pending notifications instead of sending email inside the originating request.
- An immediate WordPress Cron hook dispatches pending records, with the recurring worker remaining as fallback.
- Pending dispatch uses an atomic claim and does not increment the retry count.
- Pending records are dispatchable; only failed records are retryable.
- Nine built-in templates cover ticket creation, public replies, ticket lifecycle changes, resolution, assignment, and reassignment.
- Templates store sanitized subjects, HTML, and plain text in WordPress options.
- Canonical `{{placeholder}}` and legacy `{placeholder}` formats are rendered safely.
- Invalid or missing saved templates fall back to built-in defaults.
- Inactive variants suppress only their matching event and recipient notification.
- Queued WordPress email delivery currently uses rendered plain text; sanitized HTML is available for preview and future formatting controls.
- Administrators can list and inspect predefined templates through versioned REST endpoints.
- Template mutations require the `sbay_manage_settings` capability.
- Partial updates preserve omitted fields and apply the template service sanitization policy.
- Reset endpoints remove saved overrides and return the built-in fallback.
- API metadata exposes allowed statuses, recipient types, and editor placeholders.
- Administrators can preview sanitized draft changes without saving them.
- Preview context is generated server-side and cannot be replaced with arbitrary client data.
- Valid test recipients receive the rendered plain-text draft through WordPress `wp_mail()`.
- Test delivery attempts use the normal notification audit and failure/retry system.
- SupportBay does not store or manage SMTP configuration.
- Settings now provides shared React navigation between Email Notifications and Integrations.
- The template editor supports status, subject, HTML, plain text, and click-to-insert placeholders.
- Draft previews support desktop and mobile widths and display only server-sanitized HTML.
- Administrators can save, reset, refresh, and send a test message from the same workspace.
- Provider management remains available under the Integrations settings section.
- Installation-wide notification preferences are stored independently from template content.
- A master email switch can pause SupportBay email creation without changing templates.
- Each predefined event/recipient variant can be enabled or disabled independently.
- Delivery listeners require both the preference and matching template to be active before queueing.
- Protected REST endpoints expose partial preference updates with strict event and recipient validation.
- The React Email Notifications workspace provides consolidated master and event-recipient controls.
- Ticket close and reopen domain events now queue one customer notification when the ticket has a linked customer.
- Lifecycle delivery uses the same preference, active-template, queue, retry, and audit rules as existing ticket email.
- Built-in customer templates cover `ticket_closed` and `ticket_reopened` and appear automatically in the React editor.
- Assignment changes with a resulting agent queue `ticket_assigned:agent` for the newly assigned WordPress user.
- First-reply self-assignment, manual assignment, and bulk assignment share the same listener path.
- Unassignment and users without the SupportBay ticket-view capability do not create assignment email.
- `TicketAssignmentChanged` now carries the updated ticket, previous agent ID, and actor ID.
- Initial ownership uses `ticket_assigned`; moving ownership between agents uses `ticket_reassigned`.
- Assigning the already-current agent is a no-op and creates neither activity nor notification.
- Staff can resolve active tickets through `POST /tickets/{id}/resolve`.
- Resolution records `resolved_at`, emits `TicketResolved`, creates activity, and queues `ticket_resolved:customer`.
- Resolved tickets reject replies in both REST and React interfaces until reopened.
- Existing reopen behavior now supports resolved and closed tickets and clears final-state timestamps.
- Agent ticket details expose distinct Resolve, Close, and Reopen actions; customers can reopen resolved tickets.
- Message bodies, headers, stored payloads, and raw metadata remain unavailable to the React application.
- Notification log retention is option-backed and defaults to 90 days with bounded daily batches.
- Daily and manual cleanup remove only successful, cancelled, and retry-exhausted records older than the cutoff.
- Pending, processing, and retry-eligible failed deliveries are never selected for retention cleanup.
- Notification Delivery reporting and the Delivery Logs administrator workspace are deferred after MVP.
- Delivery logs remain internal because asynchronous dispatch, retry enforcement, auditing, and automatic retention depend on them.
- Reports provides the Ticket Performance workspace for MVP.
- `GET /sbay/v1/reports/tickets` requires `sbay_view_reports` and validates date, department, agent, and priority filters.
- Ticket summaries include created tickets, staff responses, need-reply tickets, and resolved/closed tickets.
- Daily ticket/response trends are zero-filled across the selected date range.
- Department and agent breakdowns use the same server-side filters and exclude trashed tickets.
- Ticket and notification reports expose capability-protected CSV export routes using the same validated queries as their on-screen reports.
- Export files contain summaries and all visible breakdown sections, include UTF-8 BOM compatibility, and preserve date-based filenames.
- CSV values beginning with spreadsheet formula characters are neutralized before output.
- Managers can continue viewing reports, while export controls and endpoints require `sbay_export_reports`.
- React downloads preserve server-provided filenames and release temporary browser object URLs.
- Ticket SLA is deferred until after MVP. Its Settings entry, queue controls, reports, exports, scheduled detection, and notifications are not active in the MVP runtime.
- The initial schema retains the dormant SLA breach table so the feature can return later without a destructive migration.

Future notification work

- SLA breach notifications for assigned staff
- External notification providers

---

# Completed REST API and Webhooks Foundation

```
WordPress Capability Policy

↓

Versioned Admin Ticket Routes

↓

Standard Responses and Pagination

↓

Domain Events

↓

WebhookData

↓

WebhookDispatcher Contract

↓

ApiWebhookFlowTest
```

Current API behavior

- Administrators can list and inspect tickets and messages.
- Administrators can add replies or internal notes.
- Administrators can close and reopen tickets.
- Anonymous and unauthorized requests are rejected.
- Ticket and message lifecycle events produce normalized webhook data.

Knowledge Base remains deferred until its documentation is provided.

---

# Completed Admin API Expansion

```
Customer API

↓

Department API

↓

Secret-safe Provider API

↓

Purchase Verification API

↓

Filtering and Pagination

↓

Expanded ApiWebhookFlowTest
```

All administrator resource routes are read-only in this milestone.

---

# Completed Admin API Mutations and Capability Model

```
Protected WordPress Roles

↓

SupportBay Capabilities

↓

Capability-specific REST Policies

↓

Customer and Department Mutations

↓

Provider Enable / Disable

↓

Provider-backed Verification Refresh / Revoke

↓

Expanded ApiWebhookFlowTest
```

Roles and capabilities are installed idempotently for both new and existing installations.

---

# Completed Admin Dashboard Foundation

```
WordPress Admin Menu

↓

Native Support Tickets, Reports, and Settings Pages

↓

Shared PHP Header Navigation

↓

Capability-gated Page Access

↓

Isolated React Admin Bundle

↓

Authenticated API Bootstrap

↓

WordPress-controlled Active Workspace Bootstrap

↓

ReactAdminFlowTest
```

The administrator UI is functional and intentionally uses foundation styling pending the later design pass.

---

# Immediate Next Tasks

1. ✅ Add the shared administrator/customer ticket list with server-backed search, filters, sorting, selection, and pagination.

2. ✅ Add administrator ticket conversation detail and internal-note visibility.

3. ✅ Add agent reply and internal-note forms.

4. ✅ Add close/reopen actions and React Flow Test coverage.

5. ✅ Add WordPress TinyMCE rich replies and internal notes with strict server-side sanitization.

6. ✅ Add the agent ticket sidebar with customer, ticket, verified-purchase, and activity context.

7. ✅ Add secure agent reply attachments, message attachment display, and composer cancellation.

8. ✅ Add authenticated agent downloads and submit-reply-and-close behavior.

9. ✅ Add event-driven assignment, department, priority, trash, and restore operations with activity logging.

10. ✅ Auto-assign an unassigned ticket to its first public staff responder.

11. ✅ Add server-side Ticket Queue Intelligence with accurate public-reply counts, Need Reply detection, customer/agent/department context, agent and department filters, and smart sorting.

12. ✅ Add capability-gated bulk assignment, unassignment, department, priority, trash, and restore actions with per-ticket events and partial-failure reporting.

13. ✅ Add same-customer ticket merging with transactional message and attachment re-parenting, recoverable source retirement, target queue repair, and audit activities.

14. Ticket splitting was removed from the MVP runtime and role model.

15. ✅ Add Customer 360 profiles with safe identity data, masked provider connections, purchase and ticket history, recent ticket activity, and capability-gated lifecycle controls.

16. ✅ Add the server-paginated Customer Directory with identity search, state/source filters, support-context counts, sorting, profile navigation, and capability enforcement.

17. ✅ Add the secret-safe Provider Directory inside Settings with configuration/connection health, enable/disable lifecycle controls, and React/provider flow coverage.

18. ✅ Add provider-declared configuration schemas, encrypted write-only secrets, validated configuration REST endpoints, dynamic React forms, and OAuth-backed connection health.

19. ✅ Add optional provider connection-test capabilities, normalized results, persisted health outcomes, capability-aware REST/UI controls, and deterministic flow coverage.

20. ✅ Add the server-paginated Verification Directory with combined search/provider/status filters, sorting, masked references, entitlement context, related-ticket counts, and capability-aware navigation.

21. ✅ Replace optional saved-purchase selection with provider-backed Purchase Code/Key enforcement, cache-first reuse, ownership validation, active-support gating, ticket linking, and specific customer errors.

22. ✅ Add provider-neutral OAuth token refresh, encrypted refreshed-token persistence, reconnect-required failures, and enabled/configured portal provider filtering.

23. ✅ Add customer connected-provider summaries, generic OAuth connect/reconnect routes, collision-safe current-customer linking, and React profile controls.

24. ✅ Add multi-channel-ready notification delivery logs with pending/sent/failed transitions, provider metadata, failure capture, and flow coverage.

25. ✅ Normalize all registered schemas for dbDelta, validate table creation before version updates, and add repeatable migration flow coverage.

26. ✅ Add atomic notification retry claiming, stored-payload replay, channel compatibility checks, three-attempt enforcement, and duplicate-free audit updates.

27. ✅ Add administrator-only notification log list/detail endpoints with pagination, filtering, safe diagnostics, sensitive-payload redaction, and protected manual retries.

28. ✅ Add automatic exponential notification retry scheduling, a bounded five-minute WordPress Cron worker, atomic due processing, deactivation cleanup, and deterministic flow coverage.

29. ✅ Queue initial ticket and reply emails, add atomic pending dispatch with immediate Cron triggering and bounded fallback batches, and separate first-delivery state from retry state.

30. ✅ Add option-backed notification template entities, recipient/status enums, four safe defaults, placeholder rendering, sanitization, fallback/reset behavior, listener integration, and deterministic flow coverage.

31. ✅ Add administrator-only notification template list/detail/update/reset REST endpoints, editor metadata, partial sanitized mutations, safe errors, and deterministic authorization coverage.

32. ✅ Add non-persistent sanitized draft previews, deterministic sample context, validated WordPress test-email delivery, audit logging, safe errors, and API flow coverage.

33. ✅ Add the React Email Notifications settings workspace with shared settings navigation, template selection/editing, placeholder insertion, save/reset, desktop/mobile previews, test email, responsive styling, compiled assets, and flow coverage.

34. ✅ Add option-backed installation notification preferences, a master email switch, independent event-recipient gates, protected REST management, listener enforcement, React controls, and deterministic flow coverage.

35. ✅ Add configurable customer ticket-closed and ticket-reopened templates, preference-derived event controls, asynchronous lifecycle delivery, audit integration, and end-to-end flow coverage.

36. ✅ Add configurable assigned-agent email, SupportBay capability validation, preference and template gates, manual/bulk/first-reply assignment integration, unassignment suppression, and end-to-end flow coverage.

37. ✅ Add immutable previous-assignee event context, distinct agent reassignment template and preference, specialized notification routing, same-assignee no-op enforcement, and end-to-end flow coverage.

38. ✅ Add staff ticket resolution transition, immutable event, activity logging, customer template and preference, finalized reply enforcement, resolved-ticket reopening, React controls, and end-to-end flow coverage.

39. ✅ Add React delivery-log search, filters, pagination, safe detail diagnostics, eligibility-aware manual retry, responsive styling, compiled assets, and flow coverage without exposing stored payloads.

---

# Current Workflow

Target architecture

```
VerificationService

↓

IntegrationManager

↓

PurchaseVerificationProvider

↓

EnvatoProvider

↓

EnvatoPurchaseService

↓

Envato API

↓

PurchaseVerificationData

↓

Verification Entity

↓

Database
```

VerificationService must never import

```
EnvatoProvider

EnvatoPurchaseService

EnvatoPurchase
```

---

# Do NOT Refactor

The following systems are stable.

```
Container

Repository Base

Entity Base

Events

Tickets

Messages

Activities

Attachments

Customers

Authentication

Providers Module

Verification Foundation
```

Only modify them if fixing bugs.

---

# Current Development Rules

Continue using

- Dependency Injection

- Repository Pattern

- Service Layer

- Event Driven Architecture

- Provider-independent modules

- Flow Tests

Never

- Couple Verification to Envato

- Import Providers into Modules

- Duplicate business logic

- Skip Flow Tests

---

# Next Milestone

```
Awaiting MVP Feature Confirmation
```

---

# When Starting a New Codex Session

Read

```
CURRENT_STATUS.md

↓

AGENTS.md

↓

ROADMAP.md

↓

MASTER_PLAN.md
```

Then inspect the repository.

Before editing code,

always report

1.

Current implementation.

2.

Missing implementation.

3.

Files to modify.

4.

Why those files.

Only then begin coding.

---

Status

```
Current Sprint

Custom Field Settings UI

Completed

- Categories own schema, entity, repository, service, REST controller, provider, and flow test.
- Categories support active/inactive lifecycle and optional department scope.
- Slugs are normalized and unique.
- Managers and administrators receive category-management capability.
- Tickets store an optional category ID and existing tickets remain uncategorized.
- Category persistence is included in the consolidated initial database schema.
- Customer ticket forms load active global and department-scoped categories.
- Customer ticket creation requires a category when applicable categories exist.
- Server-side validation rejects inactive and cross-department category selections.
- Portal-created tickets persist the validated category relationship.
- Staff ticket context exposes the current category and applicable choices.
- Agents can assign or clear categories from the ticket detail workspace.
- Category changes create dedicated ticket timeline activity.
- Moving a ticket clears a category that is not applicable to the new department.
- Ticket-category changes use a dedicated agent capability and server-side scope validation.
- Staff queues expose category IDs and safe category display names.
- Queue requests support exact category and explicit uncategorized filters.
- Category choices narrow to global and applicable records when a department filter is selected.
- Bulk actions can assign or clear categories while preserving per-ticket validation.
- Mixed-department bulk classification reports incompatible tickets without rolling back valid updates.
- Settings provides searchable category lifecycle management for administrators.
- Category forms manage name, slug, description, department scope, status, color, and sort order.
- Category administration is gated by the dedicated category-management capability.
- Categories referenced by tickets cannot be deleted and must be deactivated instead.
- React administrator flow coverage verifies category navigation and CRUD controls.
- Ticket performance reports support exact category and uncategorized filters.
- Report filter choices respect the selected department scope.
- Category workload includes tickets, responses, need-reply, and closed totals.
- Historical inactive categories retain their names while null records group as Uncategorized.
- Ticket CSV exports include the same filtered category workload shown on screen.
- Tags own a dedicated schema, entity, repository, service, REST controller, provider, and flow test.
- Ticket-tag relationships use a normalized junction table with unique assignment pairs.
- Tags support active/inactive lifecycle, unique sanitized slugs, and optional colors.
- Inactive tags cannot be newly assigned and in-use tags cannot be deleted.
- Managers and administrators receive the dedicated tag-management capability.
- Agents, managers, and administrators receive a separate ticket-tag mutation capability.
- Tag and ticket-tag persistence are included in the consolidated initial database schema.
- Staff ticket queues return assigned tag metadata and support exact tag filtering.
- Staff ticket details support idempotent tag addition and removal.
- Bulk actions add or remove tags from up to 100 selected tickets with per-ticket failure reporting.
- Tag assignment and removal dispatch typed domain events and create dedicated timeline activities.
- Shared React ticket components render staff-only tag labels, filters, detail controls, and bulk actions.
- Settings exposes searchable tag lifecycle management only to users with `sbay_manage_tags`.
- Administrators can create and edit tag names, slugs, colors, and active/inactive status.
- The interface explains historical inactive-tag behavior and protects deletion of tags assigned to tickets.
- React administrator flow coverage verifies capability bootstrap, navigation, CRUD calls, and deletion guidance.
- Ticket performance reports support exact tag filtering without duplicating summary tickets.
- Tag workload reports tickets, responses, need-reply, and resolved-or-closed totals.
- Multi-tag tickets appear in every applicable tag workload row, while tickets without tags group as Untagged.
- Ticket CSV exports include the same tag workload shown in the React report workspace.
- React reporting exposes active-tag filters and a dedicated By tag breakdown.
- Custom fields own definition and ticket-value schemas, entities, repository, service, REST controller, provider, and flow test.
- Definitions support text, textarea, number, select, checkbox, date, email, and URL types.
- Definitions support sanitized choices, required and customer-visible flags, optional department scope, lifecycle, and sort order.
- Ticket values are type-aware, sanitized, and unique per ticket and field.
- Definitions with historical values cannot be deleted or change type and must be deactivated instead.
- Custom-field definition and value persistence are included in the consolidated initial database schema.
- Settings exposes custom-field administration only to users with `sbay_manage_custom_fields`.
- Definition forms manage all supported types, department scope, required and customer-visible flags, lifecycle, and sort order.
- Select choices use a one-choice-per-line editor and remain server-sanitized and deduplicated.
- The UI explains type-locking, deactivation, and deletion protection for definitions with historical values.
- React administrator flow coverage verifies permission bootstrap, navigation, CRUD calls, and definition controls.
- The customer portal loads active, customer-visible custom fields for the selected department.
- Customer ticket creation renders all supported field controls and submits values keyed by definition ID.
- Required, type, option, visibility, lifecycle, and department rules are enforced server-side before ticket persistence.
- Validated custom-field values are stored against the new ticket, with creation rollback on persistence failure.
- Customer portal API and React flow coverage verify field discovery, required enforcement, rendering, submission, and storage.
- Staff ticket context returns active applicable definitions, current values, and read-only historical inactive definitions.
- Agents, managers, and administrators receive a dedicated ticket custom-field mutation capability.
- Staff value changes reuse the custom-field service for required, type, option, lifecycle, and department validation.
- The agent ticket sidebar renders controls for every supported field type, supports optional-value clearing, and reports server validation errors.
- API and React flow coverage verify context discovery, validated persistence, rejection, and typed controls.
- Ticket reports accept a selected custom field and optional exact normalized value.
- Custom-field filters use unique-ticket existence checks and therefore do not duplicate report totals.
- The selected definition receives its own value workload breakdown with ticket, response, need-reply, and final-state totals.
- Active custom-field definitions and type-aware choices are available to the protected report workspace.
- CSV exports include the same custom-field workload as the on-screen report.
- Metric and React flow coverage verify exact-value filtering, aggregation, REST metadata, controls, and export content.
- Customer-owned ticket details expose only stored values whose definitions remain customer-visible.
- Staff-only definitions and values are removed before the portal response is serialized.
- Historical inactive definitions remain readable when they still carry a stored customer-visible value.
- Customer detail renders values read-only, formats checkbox values as Yes/No, and links validated URLs safely.
- Portal API and React flow coverage verify visible value rendering and private-field non-disclosure.
- Staff ticket queries accept a custom-field definition and optional exact stored value.
- Queue filtering uses an existence subquery against the unique ticket-field relationship and preserves unique totals.
- Selecting a definition without a value finds tickets having any value for that field.
- Shared React controls are staff-only, department-aware, and type-aware for select and checkbox values.
- Customer queue controls and row payloads remain unchanged; custom-field values are not exposed in list rows.
- Custom-field, REST API, and React flow coverage verify presence, exact-value, non-match, and staff-only UI behavior.

Completed

- Custom-field set, update, and clear operations dispatch one typed domain event after successful persistence.
- Repeating an identical normalized value is a no-op and creates no duplicate audit activity.
- Ticket activities identify the field and action while deliberately excluding previous and current values.
- Customer portal creation attributes field changes to the customer; staff edits default to the agent actor.
- Flow coverage verifies each activity type, actor attribution, no-op behavior, and value non-disclosure.

- The protected ticket bulk endpoint accepts structured custom-field mutations for up to 100 tickets.
- Bulk mutations reuse the custom-field service and preserve type, required, lifecycle, and department validation.
- Compatible tickets succeed independently while keyed failures explain incompatible selections.
- Optional values can be cleared in bulk and actual changes retain normal privacy-safe activities.
- The shared staff queue renders definition and type-aware bulk controls and reports outcome counts.
- API and React flow coverage verify structured payloads, partial success, clearing, and UI wiring.

- The first public agent or manager reply automatically claims an unassigned ticket.
- Customer replies, internal notes, and later replies never overwrite an existing owner.
- Agents, managers, and administrators can assign, unassign, or transfer individual tickets from ticket details.
- Assignment targets must be eligible SupportBay staff with ticket-view permission.
- Bulk assignment remains restricted to managers and administrators.
- Ticket details show the current owner and preserve normal activity and notification workflows.
- API and React flow coverage verifies agent handoff, unassignment, current-owner rendering, and bulk restrictions.

- Administrator is listed as a protected, view-only native role.
- Support Agent, Support Manager, and custom SupportBay roles use native WordPress role capabilities.
- The role API accepts only a categorized server-owned SupportBay capability catalog.
- Descriptions, status, and selected capability definitions use option-backed metadata.
- Inactive roles retain assigned users but lose SupportBay access and cannot be newly assigned.
- Every SupportBay role can be deleted when unassigned; Administrator remains permanently protected.
- New role slugs are generated automatically by the backend and remain immutable.
- Eligible ticket agents are discovered by capability so custom support roles participate in handoff.
- Team members remain managed through WordPress Users; category-scoped access is deferred.
- Role and React flow coverage verifies authorization, protected Administrator behavior, capability filtering, lifecycle, and deletion safety.

Next Target

Department Settings Foundation
```

### Production Package Audit

- The canonical release ZIP excludes development source, flow tests, project
  documentation, internal provider manuals, and inactive MVP SLA handlers.
- Local flow-test dispatch remains available in the working tree but is removed
  from the staged production bootstrap.
- Release construction now fails closed when required runtime assets are missing,
  forbidden development artifacts leak into staging, or dev-test code remains in
  the production bootstrap.
- The CLI flow runner now exits nonzero when WordPress cannot bootstrap instead
  of reporting a false successful test run.
- Fresh authenticated WordPress users are linked to a Customer entity on first
  portal access, preventing a valid new administrator/customer session from
  receiving a portal bootstrap 403.
- The built-in Envato adapter idempotently provisions its disabled provider
  record after database installation, so fresh installations expose Envato
  settings without enabling or configuring the integration automatically.
- Portal rewrites normalize selected and shortcode page paths relative to the
  WordPress home path. Versioned rewrite refresh covers direct reloads for login,
  registration, guest tickets, and the remaining client-side portal routes.
- Customer sign-out uses an unescaped nonce-valid WordPress logout URL and
  redirects directly to the active portal page's login route without displaying
  WordPress's logout confirmation screen.
- Customer ticket creation returns its opening message identifier, separates
  post-creation attachment failures from ticket failures, and always opens the
  already-created ticket so retrying cannot create a duplicate from that state.
- Enabled photo/PDF popup preview uses the safe response MIME type on customer
  and staff ticket details, including historical JPEG rows without a preview flag.
- Ticket split services, routes, controls, capabilities, selective-move helpers,
  events, activities, and flow expectations are removed from the MVP.

- The protected active Support department is provisioned automatically and used as the server-side ticket fallback.
- Department settings now provide list, create, edit, lifecycle, priority, and ordering controls.
- The customer ticket form hides department selection when Support is the only active choice.
- Department slugs are generated by the backend and remain immutable.
- Department list actions support safe deletion; Support and departments referenced by tickets are protected.

### React Customer Authentication Foundation

- Public React routes provide SupportBay login and registration without redirecting to `wp-login.php`.
- WordPress authenticates credentials, owns password storage, and issues the normal secure login cookie.
- Registration respects the native site registration setting and creates Subscriber users linked to Customer entities.
- Existing SupportBay Customer role assignments migrate safely to Subscriber before the obsolete role is removed.
- Private portal routes redirect unauthenticated visitors to login and preserve the requested SupportBay path.
- Native password reset remains the recovery flow.
- Authentication and React portal flow coverage verifies routes, Subscriber creation, customer linking, and login.

### General Registration Setting

- General → Main includes an immediate-save registration override switch with an accessible explanatory tooltip.
- Enabled allows SupportBay Subscriber registration even when WordPress “Anyone can register” is off.
- Disabled strictly follows the native WordPress registration setting.
- Portal bootstrap and registration enforcement share the same settings service.
- Main settings can disable registration absolutely, store the guest-ticket policy, and let administrators choose the validated WordPress role assigned on registration; Subscriber remains the fallback.
- Activation provisions and selects a real Support page instead of relying on a synthetic route. `[supportbay]` mounts independently on any page, while shortcode mode controls rendering of the designated portal page.

---
