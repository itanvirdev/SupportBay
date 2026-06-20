# SupportBay – User Roles & Capabilities Specification (v1)

## Document Information

| Property     | Value                         |
| ------------ | ----------------------------- |
| Document     | 07-user-roles-capabilities.md |
| Product      | SupportBay                    |
| Version      | v1                            |
| Status       | Approved                      |
| Last Updated | June 2026                     |

---

# Purpose

This document defines SupportBay user roles, permissions, and capability management.

SupportBay follows WordPress's native role and capability system.

Roles are collections of capabilities.

Permissions should always be validated using capabilities rather than role names.

---

# Architecture Principles

## Capability Driven

Avoid:

```php
if ( $user->role === 'support_manager' ) {
    // ...
}
```

Use:

```php
if ( current_user_can( 'sbay_manage_tickets' ) ) {
    // ...
}
```

This allows custom roles to work automatically.

---

# Default Roles

SupportBay provides three custom roles:

- Customer
- Support Agent
- Support Manager

And uses WordPress Administrator.

---

# Customer

Role Key:

sbay_customer

Purpose:

Represents support customers.

Created via:

- Manual registration
- Envato OAuth
- Magic link registration
- Future providers

---

## Customer Capabilities

- sbay_view_own_tickets
- sbay_create_ticket
- sbay_reply_ticket
- sbay_upload_attachment
- sbay_view_own_profile
- sbay_edit_own_profile

---

## Restrictions

Customers cannot:

- Access staff dashboard
- View internal notes
- Assign tickets
- Manage departments
- Manage settings

---

# Support Agent

Role Key:

sbay_agent

Purpose:

Handles customer tickets.

---

## Agent Capabilities

- sbay_access_agent_dashboard
- sbay_view_tickets
- sbay_reply_ticket
- sbay_create_internal_note
- sbay_upload_attachment
- sbay_change_ticket_status
- sbay_change_ticket_priority
- sbay_move_ticket_department
- sbay_take_ticket_ownership
- sbay_view_purchase_verification

---

## Restrictions

Agents cannot:

- Manage settings
- Manage roles
- Delete departments
- Manage integrations

---

# Support Manager

Role Key:

sbay_manager

Purpose:

Supervises support operations.

---

## Manager Capabilities

Includes all Agent capabilities plus:

- sbay_assign_ticket
- sbay_reassign_ticket
- sbay_escalate_ticket
- sbay_manage_departments
- sbay_create_department
- sbay_edit_department
- sbay_disable_department
- sbay_view_reports
- sbay_refresh_verification
- sbay_manage_agents

---

## Restrictions

Managers cannot:

- Modify plugin settings
- Manage integrations
- Manage capabilities
- Delete system data

Unless explicitly granted.

---

# Administrator

Role Key:

administrator

Uses native WordPress Administrator role.

Administrators automatically receive all SupportBay capabilities.

---

# Capability Groups

## Tickets

- sbay_create_ticket
- sbay_view_own_tickets
- sbay_view_tickets
- sbay_reply_ticket
- sbay_assign_ticket
- sbay_reassign_ticket
- sbay_change_ticket_status
- sbay_change_ticket_priority
- sbay_take_ticket_ownership
- sbay_close_ticket
- sbay_reopen_ticket

---

## Departments

- sbay_manage_departments
- sbay_create_department
- sbay_edit_department
- sbay_disable_department

---

## Internal Notes

- sbay_view_internal_notes
- sbay_create_internal_note

---

## Attachments

- sbay_upload_attachment
- sbay_download_attachment
- sbay_delete_attachment

---

## Envato / Providers

- sbay_view_purchase_verification
- sbay_refresh_verification
- sbay_manage_providers

---

## Reports

- sbay_view_reports
- sbay_export_reports

---

## Settings

- sbay_manage_settings
- sbay_manage_roles
- sbay_manage_capabilities

---

# Role Management

## Custom Roles

Administrators may create additional support roles.

Examples:

- Senior Support
- Billing Agent
- Tier 2 Support
- QA Reviewer

SupportBay should not hardcode role checks.

---

# Capability Management

Administrators can:

- Create Roles
- Edit Roles
- Delete Custom Roles
- Assign Capabilities
- Revoke Capabilities

---

## Protected Roles

Cannot be deleted:

- Administrator
- Customer
- Support Agent
- Support Manager

---

# Dashboard UI

SupportBay
↓
Settings
↓
Roles & Capabilities

---

## Roles Screen

Example:

Customer

Support Agent

Support Manager

Senior Support

Billing Agent

---

## Capability Editor

Example:

Support Agent

☑ View Tickets

☑ Reply Tickets

☑ Upload Attachments

☑ Create Internal Notes

☑ Change Status

☑ Change Priority

☑ Take Ownership

☐ Assign Tickets

☐ Manage Departments

☐ Manage Settings

---

# Permission Resolution

Permissions should always be checked using capabilities.

Never:

Role Name

Always:

Capability

---

# Future Department Permissions

Potential v2 Feature

Allow department-scoped permissions.

Example:

Billing Agent

Can access:

Billing

Cannot access:

Technical Support

---

# Future Team Permissions

Potential v2 Feature

Team-based access control.

Examples:

Tier 1 Team

Tier 2 Team

Sales Team

Support Team

---

# Recommendation: Access Dashboard

```
sbay_access_dashboard
```

**Because in the future you'll have:**

- Agent Dashboard
- Manager Dashboard
- AI Dashboard
- Reports Dashboard

Instead of checking dozens of capabilities, you can simply determine whether the user can enter the SupportBay staff area at all.

---

# Approved Decisions

✓ Customer role included.

✓ Support Agent role included.

✓ Support Manager role included.

✓ WordPress Administrator supported.

✓ Administrators can create custom support roles.

✓ Administrators can edit capabilities.

✓ Permissions are capability-based.

✓ Protected system roles cannot be deleted.

✓ Future provider integrations use capability checks.

✓ Future department-level permissions supported.

---
