# 0002: Explicit producer progress and lifecycle

## Status

Accepted.

## Context

The producer submits records asynchronously. The existing convenience method also polls callbacks,
so an exception after submission does not prove that the current record was rejected. Polling is
otherwise available only through native access. Flush repeats the entire caller timeout ten times,
and destruction is not a useful point for application-owned delivery recovery.

[ADR 0001](0001-confirm-producer-delivery-results.md) already defines delivery-failure reporting.
This decision keeps that contract and the native callback-preserving implementation.

## Decision

Keep the composed, lazily initialized `KafkaProducerWrapper`. Add a serialized `ProducerRecord`
with nullable values and opaque delivery correlation, an enqueue-only operation, public polling,
and explicit close. The existing `produce()` remains enqueue followed by non-blocking polling.
It retains its existing signature except that values can also be null.

Flush performs one native wait using the supplied budget, in both producer implementations.
Callback execution can add time beyond the native wait; it cannot be preempted by this budget.
Successful close flushes, releases the wrapper's native reference, and rejects further submissions
and native access. Failed close leaves the wrapper open. Unused close does not create a client.
Existing exit callbacks remain supported for compatibility; applications own explicit shutdown.
Pass the native opaque argument only for records with correlation. Require the build capability
that also exposes `purge()` and disable error-only reports for correlated records, without changing
the caller's settings. This avoids unsupported argument counts and native opaque-string leaks.

## Consequences

Applications can distinguish submission from callback progress, correlate delivery reports, and
handle shutdown errors before committing their own state. Null values represent Kafka tombstones.
Queue-full rejection is observable through the native error code; no application resend is added.

The flush budget now bounds one native wait instead of ten waits, so timeouts can occur sooner.
Timeouts do not prove that pending records were rejected. Terminal delivery failures still require
a new producer, as recorded in ADR 0001. Caller-retained native references can bypass close and
remain caller-owned. No transaction, exactly-once, or external database atomicity is implied.
