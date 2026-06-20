# SupportBay – Department System Specification (v1)

## Document Information

| Property     | Value             |
| ------------ | ----------------- |
| Document     | 05-departments.md |
| Product      | SupportBay        |
| Version      | v1                |
| Status       | Approved          |
| Last Updated | June 2026         |

---

# Purpose

Departments help categorize and route support requests.

Every ticket belongs to exactly one department.

Departments provide organizational structure for support teams and allow managers to separate workflows such as:

- Pre-Sales
- Technical Support
- Billing
- General Support

Future versions may use departments for:

- Automatic ticket assignment
- SLA policies
- AI routing
- Team management
- Department reporting

---

# Core Principles

## One Ticket = One Department

Every ticket must belong to a single department.

Example:

Ticket #54E5DF43  
↓  
Technical Support

---

## Department Required

Department selection is required during ticket creation.

If no department is selected:

Default: General Support

---

## Customer-Friendly

Only public departments are visible to customers.

Internal departments remain hidden.

---

# Department Types

## Public Departments

Visible on ticket creation forms.

Examples:

- General Support
- Pre-Sales
- Technical Support
- Billing

Customers can select these departments.

---

## Private Departments

Not visible to customers.

Used internally by agents and managers.

Examples:

- Tier 2 Support
- Internal QA
- Escalations
- Management

Only staff can assign tickets to these departments.

---

# Department Fields

## Name

Required

Examples:

- Technical Support
- Billing

---

## Slug

Required

Examples:

technical-support
billing

Automatically generated from name.

Must be unique.

---

## Description

Optional

Used internally for documentation.

Example:

Handles product installation, bugs, and troubleshooting.

---

## Visibility

Required

Values:

- Public
- Private

Default:

Public

---

## Status

Required

Values:

- Active
- Inactive

Default:

Active

Inactive departments:

- Cannot receive new tickets.
- Remain attached to historical tickets.

---

## Sort Order

Required

Integer

Controls frontend display order.

Example:

1 → General Support  
2 → Pre-Sales  
3 → Technical Support  
4 → Billing

---

## Color

Optional

Used for UI badges and reports.

Example:

Technical Support → Blue  
Billing → Green

---

## Icon

Optional

Used in dashboard and customer portal.

Examples:

🛠 Technical Support  
💳 Billing  
🛒 Pre-Sales

---

# Department Management

## Create Department

Managers and Administrators can:

- Create
- Edit
- Disable

Departments

---

## Delete Department

Hard deletion should not be allowed.

Instead:

Status:

Inactive

Reason:

Historical tickets must remain valid.

---

# Ticket Creation

## Customer View

Department Field:

Required

Example:

Department \*
▼ Technical Support

Only public active departments appear.

---

## Staff View

Managers and Agents may:

- Change department
- Move tickets between departments

---

# Department Transfers

Managers and Agents may move tickets.

Example:

Pre-Sales  
↓  
Technical Support

System creates activity log:

Department changed from  
Pre-Sales → Technical Support

---

# Escalation Workflow

Example:

Technical Support  
↓  
Tier 2 Support

System:

- Updates department
- Logs activity
- Notifies assigned staff

---

# Permissions

## Customer

Can:

- Select public departments

Cannot:

- View private departments
- Manage departments

---

## Agent

Can:

- View all departments
- Move tickets

Cannot:

- Delete departments

---

## Support Manager

Can:

- Create departments
- Edit departments
- Disable departments
- Move tickets

---

## Administrator

Full access

---

# Reporting

Departments should support future reporting.

Examples:

Tickets by Department

Technical Support → 320  
Billing → 41  
Pre-Sales → 67

---

Average Resolution Time

Technical Support → 8h  
Billing → 2h

---

Reopened Tickets

Technical Support → 15  
Billing → 3

---

# Database Relationship

Department  
↓  
Many Tickets

One Department  
↓  
Many Tickets

One Ticket  
↓  
One Department

---

# Default Departments

Recommended Installation Defaults

- General Support
- Pre-Sales
- Technical Support
- Billing

---

# Future Enhancements

Potential v2 Features

- Department managers
- Department-specific SLAs
- Department-specific forms
- Department-specific canned replies
- Auto assignment rules
- Department working hours
- Department email addresses
- AI routing

---

# Approved Decisions

✓ Department required.

✓ One ticket belongs to one department.

✓ Public and private departments supported.

✓ Departments can be disabled.

✓ Historical tickets remain attached to inactive departments.

✓ Agents and managers can move tickets between departments.

✓ Department changes are logged.

✓ Default department: General Support.
