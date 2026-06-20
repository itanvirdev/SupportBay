# Exactly what happens when users interact with SupportBay?

# SupportBay – User Journeys v1

## Purpose

This document defines how each actor interacts with SupportBay and how the system responds to those interactions.

---

# Journey 1: Guest → Submit Ticket

## Scenario

A visitor has not purchased a product yet or chooses to contact support without authentication.

## Flow

Visitor lands on Support Page
↓
Chooses "Submit a Ticket"
↓
Selects Department
↓
Completes Ticket Form

Fields:

- First Name
- Last Name
- Email Address
- Subject
- Description (Rich Text)
- Attachments (Optional)

↓  
Submits Ticket  
↓  
SupportBay checks whether the email already exists

IF User Exists:  
↓  
Link Ticket to Existing Customer

IF User Does Not Exist:  
↓  
Create WordPress User  
Assign Role: Customer  
Set Customer State: Guest

↓  
Generate Public Track ID

Example:

Track ID: #54E5DF43

↓  
Create Ticket  
↓  
Create Initial Message  
↓  
Store Attachments  
↓  
Generate Magic Login Token  
↓  
Send Confirmation Email

Email Includes:

- Track ID
- Ticket Summary
- Magic Login Link

↓  
Ticket Status: New  
Assignment Status: Unassigned

---

# Journey 2: Envato User → OAuth → Submit Verified Ticket

## Scenario

A customer owns an Envato product and wants verified support.

## Flow

Visitor lands on Support Page  
↓  
Clicks "Continue with Envato"  
↓  
Redirect to Envato OAuth  
↓  
User Grants Permission  
↓  
SupportBay Receives Authorization

↓  
Retrieve Envato Profile

Store:

- Envato Username
- Envato User ID
- Avatar
- Access Token
- Refresh Token
- Token Expiry

↓  
Check Existing WordPress User

IF User Exists:  
↓  
Link Envato Account

IF User Does Not Exist:  
↓  
Create Customer Account

Assign Role: Customer  
Assign State: Registered

↓  
Redirect to Customer Dashboard  
↓  
Customer Clicks "Create Ticket"

Ticket Form Fields:

- Department
- Purchase Code
- Subject
- Description
- Attachments

↓  
Validate Purchase Code via Envato API

IF Verification Success:  
↓  
Store Purchase Information

Store:

- Product Name
- Purchase Code
- License Type
- Purchase Date
- Support Expiry
- Provider: Envato

↓  
Customer State → Verified

↓  
Create Ticket

Ticket Metadata:

- Verification Status: Verified
- Provider: Envato

↓  
Create Initial Message  
↓  
Store Attachments  
↓  
Send Notifications

↓  
Ticket Status: New  
Assignment Status: Unassigned

---

# Journey 3: Agent → Reply → Internal Note → Resolve Ticket

## Scenario

A support agent handles assigned tickets.

## Flow

Agent Logs In  
↓  
Opens Assigned Tickets  
↓  
Views Ticket Detail

Agent Can See:

- Customer Information
- Conversation History
- Attachments
- Purchase Verification Details
- Activity Timeline

↓  
Agent Posts Reply

Reply Types:

Customer Visible:

- Agent Reply

Internal:

- Internal Note

↓  
System Records Message  
↓  
Activity Logged

Examples:

- Reply Added
- Internal Note Added

↓  
Customer Notification Sent

(Only for Agent Replies)

↓  
Agent Updates Ticket Status

Examples:

Open  
Awaiting Customer  
On Hold  
Resolved

↓  
Ticket Updated  
↓  
Activity Logged

---

# Journey 4: Manager → Assign → Escalate → Close

## Scenario

A support manager oversees support operations.

## Flow

Manager Opens Ticket Queue  
↓  
Views Unassigned Tickets

Manager Can Filter By:

- Department
- Priority
- Status
- Agent
- Verification Status

↓  
Manager Opens Ticket

Manager Can:

- Assign Agent
- Change Priority
- Change Department
- Add Internal Notes
- Escalate Ticket
- Close Ticket

↓  
Assign Agent

Activity Logged: "Assigned to Agent"

Notification Sent: Assigned Agent

↓

Escalate Ticket

Examples: Technical Support  
↓  
Senior Technical Support

Activity Logged: "Ticket Escalated"

↓

Close Ticket

Conditions:

- Customer Issue Resolved
  OR
- No Response From Customer

↓

Ticket Status → Closed

↓

Activity Logged: "Ticket Closed"

↓

Customer Notification Sent

---

# Ticket State Transitions

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
↓  
Reopened

Additional States:

- Escalated
- Flagged
- Archived

### Additional: Customer Reply on Resolved Tickets

Should customers be allowed to reopen a resolved ticket by replying?

Example:

```
Resolved
↓
Customer replies
↓
Status automatically changes to Reopened
↓
Awaiting Agent
```

### Ticket Record State (Database Lifecycle)

This is where Active / Inactive / Trash belong.

These should never appear to customers.

```
Active
Inactive
Trash
```

**Active**
Normal tickets.  
Visible everywhere.

**Inactive**
Hidden from standard lists.  
Example:

```
Imported legacy ticket
Old archived ticket
```

**Trash**
Soft-deleted tickets.  
Manager/Admin only.  
Can be restored.  
Eventually purged.

### Multiple Purchase Codes

Can one ticket contain multiple purchase verifications?
Example:

```
One Ticket
↓
One Purchase Verification
↓
One Product
```

```
Ticket #A
↓
Fixton Theme
↓
Purchase Code ABC

Ticket #B
↓
Rovix Theme
↓
Purchase Code XYZ
```

---

# Assignment Lifecycle

Unassigned  
↓  
Assigned  
↓  
Escalated  
↓  
Reassigned  
↓  
Completed

### Additional: Escalation Behavior

When a manager escalates a ticket:

```
Technical Support
↓
Senior Support
```

Should SupportBay:

- Reassign automatically?
- Only change department?
- Ask the manager to choose an agent?

---

# Updated SupportBay Decisions

```
✓ Public Track ID (#54E5DF43)

✓ Manual Assignment (Manager)

✓ Departments in v1

✓ Magic Login Links

✓ Customer Replies Reopen Tickets

✓ One Ticket = One Product

✓ One Ticket = One Purchase Verification

✓ Ticket Statuses:
    New
    Open
    Awaiting Customer
    Awaiting Agent
    Resolved
    Closed
    Reopened

✓ Record States:
    Active
    Inactive
    Trash

✓ Operational Flags:
    Escalated
    Flagged
    Archived
```

---

# Customer State Lifecycle

Guest  
↓  
Registered  
↓  
Verified

Optional:

Verified  
↓  
Suspended

---

# Department Examples

Public Departments:

- Pre-Sales
- Technical Support
- Billing

Private Departments:

- Tier 2 Support
- Internal QA

---

# Notification Events

Customer Notifications:

- Ticket Created
- Agent Replied
- Ticket Resolved
- Ticket Closed
- Magic Login Link Generated

Agent Notifications:

- Ticket Assigned
- Ticket Escalated
- Customer Replied

Manager Notifications:

- Escalation Requested
- SLA Warning (Future)

---

# Security Rules

Magic Login Links:

- Single Use
- Expire After 24 Hours
- Invalidated After Password Setup

Purchase Verification:

- Validate Against Envato API
- Store Verification Snapshot
- Mask Purchase Codes in UI

Attachments:

- Access Restricted to Authorized Users
- Download Permission Checked Before Serving Files

---
