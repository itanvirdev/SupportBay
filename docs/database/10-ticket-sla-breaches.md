# Ticket SLA Breaches

## Table: `wp_sbay_ticket_sla_breaches`

Stores durable evidence that a ticket crossed a configured SLA metric for the
first time. The unique `(ticket_id, metric)` key is the concurrency and
idempotency boundary for scheduled detectors.

Current metric:

- `first_response`

Each record stores the target minutes and breach timestamp used when the event
was detected. Detection currently applies only to active, non-final,
unanswered tickets and uses calendar minutes.

Repositories claim records atomically before services dispatch domain events.
Listeners may create timeline activities, notifications, or future escalation
side effects without duplicating the breach fact.
