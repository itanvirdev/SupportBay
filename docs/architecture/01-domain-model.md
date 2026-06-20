# What are the things that exist inside SupportBay?

# SupportBay Domain Model v1

## Purpose

SupportBay is a support platform designed for digital product sellers. It enables businesses to verify customers, manage support tickets, and provide efficient assistance through a centralized support portal.

---

# Actors (Who interacts with the system?)

## 1. Guest Visitor

Someone who has not authenticated.

### Capabilities

- Submit pre-sale inquiries
- Upload attachments
- Receive email notifications
- Automatically converted into a customer account after ticket submission

---

## 2. Customer

A registered user seeking support.

### Customer Types

#### Verified Customer

Authenticated and has a verified purchase.

**Examples:**

- Envato customer
- EDD customer _(future)_
- WooCommerce customer _(future)_

**Capabilities:**

- Create support tickets
- View own tickets
- Reply to tickets
- Upload attachments
- View purchase information

#### Guest Customer

Created automatically from a pre-sale inquiry.

**Capabilities:**

- View their own tickets
- Reply via portal
- Upgrade to verified status later

---

## 3. Support Agent

Handles support requests.

### Capabilities

- View assigned tickets
- Reply to customers
- Add internal notes
- Change ticket status
- Assign priorities

---

## 4. Support Manager

Supervises support operations.

### Capabilities

- View all tickets
- Assign agents
- Reassign tickets
- Access reports
- Configure workflows

---

## 5. Administrator

Manages the entire SupportBay system.

### Capabilities

- Manage settings
- Configure integrations
- Manage roles
- Perform maintenance tasks
- Access all reports

---

## 6. External Provider

Third-party systems used for verification.

### Initial Provider

- Envato

### Future Providers

- Easy Digital Downloads
- WooCommerce

---

# Core Entities

These are the most important objects in the system.

---

## Ticket

Represents a support request.

A ticket contains:

- Ticket number
- Subject
- Status
- Priority
- Customer
- Assigned agent
- Provider information
- Verification status
- Created date
- Updated date

---

## Message

Represents communication within a ticket.

### Message Types

- Customer Reply
- Agent Reply
- Internal Note
- System Event

Each message belongs to exactly one ticket.

---

## Attachment

Represents uploaded files.

### Supported Initially

- Images
- PDFs
- ZIP archives

Attachments belong to messages.

---

## Purchase Verification

Represents proof of ownership.

### Initial Provider

- Envato Purchase Code

### Stores

- Product name
- License type
- Purchase date
- Support expiry date
- Purchase identifier
- Provider source

---

## Assignment

Represents ticket ownership.

### Stores

- Assigned agent
- Assignment date
- Assigned by

---

## Activity Log

Represents important actions.

### Examples

- Ticket created
- Agent assigned
- Status changed
- Priority changed
- Internal note added

---

## Notification

Represents alerts sent by the system.

### Examples

- New ticket created
- New reply received
- Ticket closed
- Ticket assigned

---

# Ticket Lifecycle

```text
New
↓
Open
↓
Awaiting Customer
↓
Awaiting Agent
↓
Resolved
↓
Closed
```

### Additional Statuses

```
Trash
Re-open
Active
Inactive
```

---

# Ticket Priority Levels

```
Normal
Medium
High
Urgent
```

---

# Additional Data (for verified)

```
Purchase Code:
Product Name:
License Type:
Support Until: June 01, 2027
Site: [purchased from eg. themeforest.net]
```

---

# Important Business Rules

## Rule 1

A ticket always belongs to one customer.

---

## Rule 2

A customer may have many tickets.

---

## Rule 3

A ticket contains many messages.

---

## Rule 4

A message may contain many attachments.

---

## Rule 5

Internal notes are messages that are hidden from customers.

---

## Rule 6

A purchase verification is optional but for Envato, EDD, WooCommerce tickets mandatory the purchase verification.

Pre-sale tickets can exist without verification.

---

## Rule 7

One customer may have multiple provider accounts in the future.

### Example

```text
Customer
├─ Envato
├─ EDD
└─ WooCommerce
```

---

# Questions We Need to Decide Next

Before moving to database design, we need to finalize these decisions.

---

## 1. Ticket Number Format

### Internal ID (Database)

```
1646
```

**Used for:**

- Database relations
- REST API operations
- Internal processing

### Public Track ID

```
#54E5DF43
```

**Used for:**

- Customer communication
- Email notifications
- Searching tickets
- Magic login pages

---

## 2. Agent Assignment Strategy

### Manual Assignment (Manager Controlled)

**Workflow:**

```text
New Ticket
↓
Unassigned Queue
↓
Manager Reviews
↓
Assign Agent
↓
Agent Owns Ticket
```

### Allow agents to self-claim tickets

**Example:**

```text
Unassigned
↓
Agent clicks "Take Ownership"
↓
Assigned automatically
```

**Controlled by settings:**

```text
Enable self-assignment?
☐ No
☑ Yes
```

---

## 3. Departments (v1)

**Proposed Structure**

```text
Department
├── Name
├── Slug
├── Description
├── Visibility
├── Default Agent
├── Active Status
└── Sort Order
```

**Examples**

- Pre-Sales
- Technical Support
- Billing

### Ticket Form

**Ticket Form**

```
Department *
▼ Technical Support
```

### Manager Dashboard

**Managers can:**

```
Create Department
Edit Department
Delete Department
Disable Department
Set Default Agent
```

### Should customers always choose the departments?

```
visibility = public | private
```

---

## 4. Guest User Access

**Guest Flow**

```
Guest submits ticket
↓
WP Customer account created
↓
Magic login token generated
↓
Email sent
↓
Customer clicks link
↓
Automatically logged in
↓
Redirected to ticket
↓
Profile prompts:
"Set your password"
```

**Rules:**

- Invalidated after password setup until magic login will work.
