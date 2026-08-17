# Ticket Custom Fields

Custom fields extend ticket metadata without adding provider-specific columns to the ticket table.

`wp_sbay_custom_fields` stores global or department-scoped definitions. A definition has a unique slug, supported field type, optional select choices, required and customer-visible flags, active/inactive lifecycle, and deterministic sort order.

`wp_sbay_ticket_custom_field_values` stores one normalized string value for each ticket and field pair. The unique `(ticket_id, field_id)` key makes repeated saves an update rather than a duplicate. `updated_by` records the last WordPress user responsible for the value.

Supported foundation types are `text`, `textarea`, `number`, `select`, `checkbox`, `date`, `email`, and `url`. External values are sanitized and validated by `CustomFieldService` before persistence. Select values must match the stored sanitized choices.

Definitions with historical ticket values cannot be deleted or have their type changed. They may be deactivated, preserving historical meaning while preventing use for new values.

The customer ticket form requests active, customer-visible definitions applicable to the selected department. The server validates every submitted field ID, required value, type, and choice before creating the ticket. Accepted values are stored only after the ticket and opening message exist; partial creation is rolled back when value persistence fails.

Staff ticket context includes every active definition applicable to the ticket department and any inactive definition needed to explain a historical stored value. Authorized staff edit active values through the same type-aware service validation used during customer creation. Optional empty values remove the stored row; required values cannot be cleared. Historical inactive fields are visible but read-only.

Ticket performance reports may select one active definition and optionally require an exact normalized value. Filtering uses an `EXISTS` condition against the unique ticket-and-field value rows, preserving unique ticket totals. When a definition is selected, the report returns a workload breakdown by its stored values and includes the same section in CSV exports. Reporting never combines values from unrelated definitions.

Customer ticket details return stored values only when the current definition remains customer-visible. This filtering happens in the service layer before REST serialization. Inactive historical definitions may remain visible when they retain that flag and have a stored value; definitions marked staff-only never expose their labels or values. Detail responses omit options, lifecycle state, updater IDs, timestamps, and empty definitions.

The staff ticket queue accepts a selected definition and optional exact stored value. Its repository uses an `EXISTS` subquery against `wp_sbay_ticket_custom_field_values`, so each ticket remains a single queue row. A field without a value means the ticket has any stored value for that definition. Queue responses do not include custom-field values; authorized staff retrieve them only from ticket context.

The Settings workspace manages these definitions through the protected REST controller. WordPress supplies only a capability-derived `canManageCustomFields` bootstrap flag to React; the server independently enforces every mutation.

Successful value creation, modification, and clearing dispatch a typed custom-field domain event. The activity listener records the responsible actor, field ID, field name, and action. It never stores the previous or current field value because custom fields may contain credentials or other sensitive customer data. Saving the same normalized value twice is intentionally silent.

Staff may apply one active definition and value to up to 100 selected tickets. Bulk updates reuse `setValue()` for every ticket, so mixed departments and invalid values produce per-ticket failures without discarding compatible updates. Optional fields may be cleared in bulk, and every actual change retains its normal audit activity.
