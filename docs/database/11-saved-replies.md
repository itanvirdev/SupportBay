# Saved Replies

Table: `wp_sbay_saved_replies` (using the active WordPress table prefix).

Saved replies are reusable, staff-managed rich-text fragments. They are independent from tickets and messages; selecting one never creates a message until the normal reply workflow is submitted.

Fields:

- `id`: internal primary key
- `title`: searchable staff-facing label
- `content`: sanitized supported rich text
- `category`: optional sanitized organizational label
- `department_id`: optional applicability scope; `NULL` means global
- `status`: `active` or `inactive`
- `created_by`: WordPress user ID of the creator
- `usage_count`: atomic composer insertion count
- `last_used_at`: most recent insertion timestamp
- `last_used_by`: WordPress user ID that most recently inserted it
- `created_at`, `updated_at`: WordPress-local timestamps

Indexes cover status, category, department, creator, title, usage count, and last-used time. Rich text must be sanitized by `SavedReplyService` before persistence. Usage means insertion into a staff composer; it does not claim the draft was submitted unchanged.
