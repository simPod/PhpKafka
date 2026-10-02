# Confirm producer delivery results

## Status

Accepted.

## Context

Native flush reports local queue completion. Records that fail permanently or expire also leave the queue, so
successful flush does not by itself report success for every record. Native polling can serve reports before flush.
The public configuration and producer APIs allow application callbacks, reused configurations, and direct native
publication. Those APIs must keep working.

## Decision

Observe native delivery reports for each producer instance and retain the first unsuccessful report as scalar
diagnostics. Surface a library-owned `DeliveryFailed` exception outside native callbacks. Flush drains first, then
checks the retained delivery result and native flush result. Keep configured acknowledgment, idempotence, report
filtering, and timeout behavior unchanged. Apply the result check to both producer APIs.

Multiplex delivery observers with the existing user callback through a native configuration subclass. Use weak,
producer-identity keys so reused configurations neither share failure state nor retain wrappers. Preserve each
native producer's construction-time user callback and callback exception behavior.

Keep report failures sticky for explicit calls. During destruction, suppress only the duplicate presentation of a
report failure already surfaced explicitly. Applications own logging and recovery for new shutdown errors and must
confirm delivery before committing their own source state.

## Consequences

- Callers can distinguish queue completion from successful delivery under their configured acknowledgment policy.
- Terminal record errors require a fresh producer wrapper; incomplete native flush can be retried.
- User callbacks and direct native access remain supported.
- Idempotence and Kafka transactions retain their native scope. The wrapper does not provide an atomic transaction
  with an external database or guarantee downstream execution.
- Invalid negative partitions remain input errors. Native enqueue failures preserve their cause, and user callback
  failures are not reclassified as delivery errors.
