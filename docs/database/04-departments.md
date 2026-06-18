# SupportBay – Database Specification

## Table: `wp_sbay_departments`

---

# Purpose

Stores support departments used to categorize and route tickets.

Each ticket must belong to one department.

Departments help organize support workflows, reporting, and future automation.

---

# Relationships

```text id="r1"
Department
   ↓
Tickets
   ↓
Messages
```

---

# Table Structure

| Column               | Type            | Null | Default           | Index   | Description                         |
| -------------------- | --------------- | ---- | ----------------- | ------- | ----------------------------------- |
| id                   | BIGINT UNSIGNED | No   | AUTO_INCREMENT    | PRIMARY | Department ID                       |
| name                 | VARCHAR(100)    | No   | -                 | UNIQUE  | Department name                     |
| slug                 | VARCHAR(120)    | No   | -                 | UNIQUE  | URL-safe identifier                 |
| description          | TEXT            | Yes  | NULL              | -       | Optional description                |
| is_active            | TINYINT(1)      | No   | 1                 | INDEX   | Enable/disable department           |
| sort_order           | INT UNSIGNED    | No   | 0                 | INDEX   | Display order                       |
| auto_assign_agent_id | BIGINT UNSIGNED | Yes  | NULL              | INDEX   | Default agent (optional v1 feature) |
| default_priority     | VARCHAR(20)     | No   | normal            | INDEX   | Default ticket priority             |
| metadata             | LONGTEXT        | Yes  | NULL              | -       | JSON config for future rules        |
| created_at           | DATETIME        | No   | CURRENT_TIMESTAMP | INDEX   | Created time                        |
| updated_at           | DATETIME        | No   | CURRENT_TIMESTAMP | INDEX   | Updated time                        |

---

# Default Department

System default:

```text id="d3"
General Support
```

Rules:

- Cannot be deleted
- Can be renamed
- Always fallback if missing

---

# Department Slug

Used for:

- internal APIs
- admin filters
- future routing rules

Example:

```text id="d4"
technical-support
billing
pre-sales
```

---

# Assignment Behavior (v1)

When ticket is created:

1. Department selected
2. If `auto_assign_agent_id` exists → assign ticket
3. Else → ticket goes to Unassigned Queue

---

# Sorting

Departments are displayed by:

```text id="d5"
sort_order ASC
name ASC
```

---

# Activation Rules

If:

```text id="d6"
is_active = 0
```

Then:

- cannot be selected in new tickets
- existing tickets remain unchanged
- still visible in admin (optional filter)

---

# Business Rules

- Every ticket must belong to one department
- Departments can be enabled/disabled
- Departments do not delete historical ticket data
- Department changes do NOT affect messages
- Department is used for filtering, routing, and reporting
- Default department is always available

---

# Index Strategy

- PRIMARY: id
- UNIQUE: name
- UNIQUE: slug
- INDEX: is_active
- INDEX: sort_order
- INDEX: auto_assign_agent_id
- INDEX: default_priority
- INDEX: created_at

---

# Repository Responsibilities

Department Repository handles:

- CRUD operations
- listing active departments
- sorting
- validation
- assignment helpers

Business rules handled in service layer.

---

# Admin Use Cases

Departments can be managed from dashboard:

- Create new department
- Rename department
- Disable department
- Reorder departments
- Assign default agent (optional)
- Set default priority

---

# Future Extensions (v2+)

- Department-level SLA rules
- AI routing per department
- Department-specific email templates
- Department-based live chat routing
- Multi-team departments
- Hierarchical departments
- Department-specific dashboards

---

# Integration with Tickets

Ticket table uses:

```text id="d7"
department_id
```

Behavior:

- Required field
- Selected during ticket creation
- Editable by agents/managers only

---

# Approved Decisions

✓ Required field for tickets
✓ Flat structure (v1)
✓ Default department included
✓ Optional auto-assignment
✓ Sortable list
✓ Activatable/deactivatable departments
✓ Future-ready metadata field
✓ No hierarchy complexity in v1

---
