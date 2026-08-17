```
Phase A
Planning & Architecture
        ✅ Completed

Phase B
Development
        ↓

B1. Project Bootstrap
B2. Core Framework
B3. Authentication
B4. Ticket System
B5. Messaging
B6. Attachments
B7. Departments
B8. Envato Integration
B9. Notifications
B10. Dashboard & Settings

↓

Phase C
Testing & QA

↓

Phase D
WordPress.org Release

↓

Phase E
Premium Features
```

I would build SupportBay in this order:

### Stage 1 — Plugin Foundation

composer.json
supportbay.php
Application.php
Activator.php
Deactivator.php
Uninstaller.php
Constants.php
Container.php
ServiceIntegrationRegistry.php

Result: The plugin loads successfully.

Repositories  
Service Providers  
Module Registry

### Module Tickets

```
Modules/
└── Tickets/
    ├── Database/
    │   └── TicketSchema.php
    ├── Entities/
    │   └── Ticket.php
    ├── Enums/
    │   ├── TicketPriority.php
    │   ├── TicketState.php
    │   └── TicketStatus.php
    ├── Http/
    │   └── Controllers/
    │           └── TicketController.php
    ├── Repositories/
    │   └── TicketRepository.php
    ├── Services/
    │   └── TicketService.php
    └── TicketServiceProvider.php
```

### Module Messages

```
Modules/
└── Messages/
        │
        ├── Database/
        │   └── MessageSchema.php
        │
        ├── Enums/
        │   ├── MessageType.php
        │   └── MessageVisibility.php (if needed)
        │
        ├── Entities/
        │   └── Message.php
        │
        ├── Repositories/
        │   └── MessageRepository.php
        │
        ├── Services/
        │   └── MessageService.php
        │
        ├── Http/
        │   └── Controllers/
        │
        └── MessageServiceProvider.php
```

#### ✅ Successfully done flow test

## Base Architecture

- /Core/Database/Repository.php (refactor the Ticket & Message Repository)
- /Core/Entities/Entity.php

### Module Departments

```
Modules/
└── Departments/
    ├── Database/
    │   └── DepartmentSchema.php
    ├── Enums/
    │   └── DepartmentStatus.php
    ├── Entities/
    │   └── Department.php
    ├── Repositories/
    │   └── DepartmentRepository.php
    ├── Services/
    │   └── DepartmentService.php
    ├── DepartmentServiceProvider.php
    └── Controllers/ (later)
```

#### ✅ Successfully done department flow test

## Events Base

```
Core/
└── Events/
    ├── Contracts/
    │   ├── Event.php
    │   └── Listener.php
    │
    ├── EventDispatcher.php
    ├── EventServiceProvider.php
    ├── EventManager.php          // facade/helper (letter)
    │
    ├── AbstractEvent.php
    │
    └── Listeners/
```

### Message Event

```
Modules/
└── Messages/
    └── Events/
    |   └── MessageCreated.php
    └── Listeners/
        └── SyncTicketReplyListener.php
```

### Event System

### Stage 2 — Core Infrastructure

```
Core/Events/ListenerRegistry.php
Refactor EventDispatcher.php
Refactor Base ServiceProvider.php
Refactor the MessageServiceProvider.php
Refactor the Container.php
Refactor EventServiceProvider, TicketServiceProvider, MessageServiceProvider, and DepartmentServiceProvider
```

### Module Activities

```
Modules/
└── Activities/
    ├── ActivityServiceProvider.php
    │
    ├── Database/
    │   └── ActivitySchema.php
    │
    ├── Entities/
    │   └── Activity.php
    │
    ├── Repositories/
    │   └── ActivityRepository.php
    │
    ├── Services/
    │   └── ActivityService.php
    │
    ├── Enums/
    │   └── ActivityType.php
    │
    ├── Listeners/
    │   └── LogMessageCreatedActivity.php
    │
    └── Tests/
        └── ActivityFlowTest.php
```

```
Refactor Module Entities
```

#### ✅ Successfully done activity flow test

### Module Attachments

Modules/
└── Attachments/
├── Database/
│ └── AttachmentSchema.php
│
├── Enums/
│ ├── AttachmentCategory.php
│ ├── AttachmentState.php
│ ├── ScanStatus.php
│ └── StorageDisk.php
│
├── Entities/
│ └── Attachment.php
│
├── Repositories/
│ └── AttachmentRepository.php
│
├── Services/
│ └── AttachmentService.php
│
├── Events/
│ ├── AttachmentUploaded.php
│ └── AttachmentDeleted.php
│
├── Listeners/
│ ├── LogAttachmentUploadedActivity.php
│ └── LogAttachmentDeletedActivity.php
│
└── AttachmentServiceProvider.php

##### AttachmentFlowTest.php

Rewrite the flow tests

#### ✅ Successfully done attachment flow test

#### Add core testing base

```
includes/
└── Core/
    └── Testing/
        ├── Assert.php
        └── FlowTest.php
```

- rewrite the AttachmentFlowTest.php ✅
- rewrite the ActivityFlowTest.php ✅
- rewrite the TicketFlowTest.php ✅
- rewrite the DepartmentFlowTest.php ✅
- rewrite the MessageFlowTest.php ✅

### Module Customer

```
Modules/
└── Customers/
    │
    ├── CustomerServiceProvider.php
    │
    ├── Database/
    │   └── CustomerSchema.php
    │
    ├── Entities/
    │   └── Customer.php
    │
    ├── Enums/
    │   ├── CustomerState.php
    │   └── CustomerSource.php
    │
    ├── Repositories/
    │   └── CustomerRepository.php
    │
    ├── Services/
    │   └── CustomerService.php
    │
    ├── Events/
    │   ├── CustomerCreated.php
    │   ├── CustomerUpdated.php
    │   └── CustomerStateChanged.php
    │
    ├── Listeners/
    │   ├── LogCustomerCreatedActivity.php
    │   ├── LogCustomerUpdatedActivity.php
    │   └── LogCustomerStateChangedActivity.php
    │
    ├── Validators/
    │   └── CustomerValidator.php
    │
    └── Tests/
        └── CustomerFlowTest.php
```

#### ✅ Successfully done customer flow test

### Module Auth

```
Modules/
└── Auth/
    ├── AuthServiceProvider.php
    │
    ├── Database/
    │   └── AuthTokenSchema.php
    │
    ├── Entities/
    │   └── AuthToken.php
    │
    ├── Enums/
    │   ├── AuthTokenType.php
    │   └── AuthTokenState.php
    │
    ├── Repositories/
    │   └── AuthTokenRepository.php
    │
    ├── Services/
    │   └── AuthService.php
    │
    ├── Events/
    │   ├── AuthTokenCreated.php
    │   ├── AuthTokenAuthenticated.php
    │   └── AuthTokenRevoked.php
    │
    ├── Listeners/
    │   ├── LogAuthTokenCreatedActivity.php
    │   ├── LogAuthTokenAuthenticatedActivity.php
    │   └── LogAuthTokenRevokedActivity.php
    │
    └── Tests/
        └── AuthFlowTest.php

✅ AuthServiceProvider

✅ AuthFlowTest
```

### Providers Module

```
includes/
└── Core/
    ├── Foundation/
    │   ├── ServiceProvider.php
    │   └── ServiceProviderRegistry.php
    │
    └── Providers/
        ├── Contracts/
        │   ├── IntegrationProvider.php
        │   ├── OAuthProvider.php          (future)
        │   ├── PurchaseProvider.php       (future)
        │   ├── WebhookProvider.php        (future)
        │   └── ProductSyncProvider.php    (future)
        │
        ├── IntegrationRegistry.php
        ├── IntegrationManager.php
        └── IntegrationDiscovery.php
```

```
Modules/
└── Providers/
    ├── Database/
    │   └── ProviderSchema.php
    │
    ├── Entities/
    │   └── Provider.php
    │
    ├── Enums/
    │   ├── ProviderCategory.php
    │   └── ProviderStatus.php
    │
    ├── Repositories/
    │   └── ProviderRepository.php
    │
    ├── Services/
    │   └── ProviderService.php
    │
    ├── Events/
    ├── Listeners/
    ├── ProviderServiceProvider.php
    └── Tests/

✅ ProviderFlowTest
```

### Envato Provider

```
includes/
└── Providers/
    └── Envato/
        │
        ├── EnvatoProvider.php
        ├── EnvatoServiceProvider.php
        │
        ├── Api/
        │   └── EnvatoApiClient.php
        │
        ├── Services/
        │   ├── EnvatoOAuthService.php
        │   ├── EnvatoPurchaseService.php
        │   └── EnvatoCustomerService.php
        │
        ├── Data/
        │   ├── EnvatoCustomer.php
        │   ├── EnvatoPurchase.php
        │   └── EnvatoToken.php
        │
        ├── Exceptions/
        │   └── EnvatoException.php
        │
        ├── Routes/
        │   └── OAuthRoutes.php
        │
        ├── README.md
        └── MANUAL_TESTING.md
```

### Purchase Verification

```
Modules/
└── Verifications/
    ├── Contracts/
    │   └── VerificationProvider.php (future)
    │
    ├── Database/
    │   ├── PurchaseVerificationSchema.php
    │   └── VerificationMigration.php
    │
    ├── Entities/
    │   └── Verification.php
    │
    ├── Enums/
    │   ├── VerificationStatus.php
    │   ├── SupportStatus.php
    │   └── VerificationSource.php
    │
    ├── Events/
    │
    ├── Listeners/
    │
    ├── Repositories/
    │   └── VerificationRepository.php
    │
    ├── Services/
    │   └── VerificationService.php
    │
    ├── VerificationServiceProvider.php
    └── README.md

✅ VerificationFlowTest
```

Contracts
Helpers
Support
Exceptions

Result:

Internal framework is operational.

### Stage 3 — Database Layer

Create repositories for:

Tickets ✅
Messages ✅
Attachments ✅
Activities ✅
Departments ✅
Auth Tokens ✅
Purchase Verifications ✅
Providers ✅
Notification Logs ✅

At this stage, do not implement business logic yet.

### Stage 4 — Services

Build:

TicketService ✅

MessageService ✅

AttachmentService ✅

DepartmentService ✅

AuthService ✅

NotificationService ✅

EnvatoService

This is where the business logic lives.

### Stage 5 — REST API

Tickets

Messages

Departments

Providers

Auth

Settings

Admin Notification Logs ✅

Manual Notification Retry ✅

Scheduled Notification Retry Worker ✅

Asynchronous Initial Notification Queue ✅

Email Template Foundation ✅

Administrator Notification Template REST API ✅

Notification Template Preview and Test Email ✅

React Notification Template Settings ✅

Notification Preferences and Event Enablement ✅

Ticket Lifecycle Notifications ✅

Ticket Assignment Notifications ✅

Ticket Reassignment Notifications ✅

Ticket Resolution Workflow and Notification ✅

React Notification Delivery Diagnostics ✅

Notification Log Retention and Cleanup ✅

Notification Delivery Metrics and Reporting ✅

Ticket Performance Reporting ✅

Report Export Foundation ✅

Ticket SLA Metrics and Response-Time Bands ✅

SLA Due-State and Ticket Queue Indicators ✅

Scheduled SLA Breach Detection and Domain Events ✅

SLA Breach Notifications for Assigned Staff ✅

Saved Replies Foundation ✅

React Saved Reply Composer Integration ✅

Saved Reply Management Settings UI ✅

Saved Reply Usage Tracking ✅

Saved Reply Sorting by Usage ✅

Saved Reply Categories ✅

Saved Reply Dynamic Placeholders ✅

Saved Reply Department Scoping ✅

Ticket Categories Foundation ✅

Customer Ticket Category Selection ✅

Staff Ticket Category Changes and Activity ✅

Ticket Category Queue Filtering and Bulk Classification ✅

Category Management Settings UI ✅

Ticket Category Reporting ✅

Ticket Tags Foundation ✅

Ticket Tag Workflow Integration ✅

Ticket Tag Settings UI ✅

Ticket Tag Reporting ✅

Ticket Custom Fields Foundation ✅

Custom Field Settings UI ✅

Customer Custom Field Ticket Creation ✅

Staff Custom Field Ticket Workflow ✅

Custom Field Reporting Foundation ✅

Customer Custom Field Detail Visibility ✅

Custom Field Queue Filtering ✅

Custom Field Change Audit Trail ✅

Custom Field Bulk Update Workflow ✅

First Reply Ownership and Agent Handoff ✅

User Role Settings Foundation ✅

### Stage 6 — React

Build:

Dashboard

Customer Portal

Agent Dashboard

Settings

Providers
One thing I would change

After everything we've designed, I would avoid building around the database tables first.

Instead, build around the modules.

For example:

Modules/

Tickets/
Database/
Repository/
Service/
Http/
React/

Messages/
...

Auth/
...

Providers/
...

This keeps each feature self-contained and aligns with the module architecture we designed.

```
supportbay/
│
├── assets/
├── docs/
├── includes/
│   ├── Core/
│   ├── Contracts/
│   ├── Modules/
│   ├── Providers/
│   ├── Support/
│   ├── Helpers/
│   ├── Database/
│   ├── Http/
│   ├── Exceptions/
│   └── Functions/
│
├── languages/
├── templates/
├── tests/
├── vendor/
│
├── composer.json
├── package.json
├── supportbay.php
└── uninstall.php
```
