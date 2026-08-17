# Ticket Custom Fields

Custom fields extend ticket metadata without adding provider-specific columns to the ticket table.

`wp_sbay_custom_fields` stores global or department-scoped definitions. A definition has a unique slug, supported field type, optional select choices, required and customer-visible flags, active/inactive lifecycle, and deterministic sort order.

`wp_sbay_ticket_custom_field_values` stores one normalized string value for each ticket and field pair. The unique `(ticket_id, field_id)` key makes repeated saves an update rather than a duplicate. `updated_by` records the last WordPress user responsible for the value.

Supported foundation types are `text`, `textarea`, `number`, `select`, `checkbox`, `date`, `email`, and `url`. External values are sanitized and validated by `CustomFieldService` before persistence. Select values must match the stored sanitized choices.

Definitions with historical ticket values cannot be deleted or have their type changed. They may be deactivated, preserving historical meaning while preventing use for new values. Customer visibility is only definition metadata in this milestone; customer and staff form integration is deferred.
