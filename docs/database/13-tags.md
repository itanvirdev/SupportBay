# Ticket Tags

Ticket tags provide reusable global labels independent of the single category relationship.

`wp_sbay_tags` stores a unique slug, display name, optional color, active/inactive status, and WordPress-local timestamps. `wp_sbay_ticket_tags` is the many-to-many junction between tickets and tags and records the optional assigning WordPress user and assignment time.

The junction enforces a unique `(ticket_id, tag_id)` pair, making repeated assignment idempotent. Tag services reject inactive or missing tags for new assignments. Tags referenced by tickets cannot be deleted; administrators must deactivate them so historical ticket labels remain available.

Tags are global in this foundation. Department-scoped applicability is intentionally not introduced because tags are intended as flexible multi-label metadata, while Categories own structured department-aware classification.

## Ticket Workflow

Staff ticket queues return assigned tags and accept an exact `tag_id` filter. Ticket detail actions and bulk operations add or remove relationships through `TagService`, preserving active-tag validation and idempotency.

Actual relationship changes dispatch `TicketTagChanged`. The activity listener records separate `tag_added` and `tag_removed` timeline entries with the tag name and acting staff user. Repeating an existing assignment or removing an absent assignment is a no-op and does not duplicate activity.

## Reporting

Ticket reports accept an exact `tag_id` filter through an `EXISTS` query, so unique summary tickets are not duplicated by the many-to-many relationship. Tag workload uses the same report filters but expands each ticket into every assigned tag group. Consequently, workload row totals may exceed the unique ticket summary when tickets have multiple tags. Tickets without assignments appear under `Untagged`.
