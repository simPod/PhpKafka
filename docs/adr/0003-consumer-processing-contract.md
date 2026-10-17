# Consumer processing and partition ownership

## Status

Accepted for the consumer contract rework. This is a separate change from native configuration and producer lifecycle work.

## Context

The legacy native subclass replaced user callbacks, installed process signals, accumulated mutable batches and
used second-resolution wall-clock deadlines. It did not drain partial batches on stop or before partition revocation.
The documented batch acknowledgment committed only the last message, missing other topic-partitions. Native
assignment-wide commits and background auto-commit can instead acknowledge fetched but unprocessed records.

An existing native consumer does not expose its installed rebalance callback. Accepting one for managed batching
would falsely imply that the library can observe ownership changes. ext-rdkafka 6.x also does not guarantee
incremental assignment methods: support was added in 6.0.4 and depends on the native build.
Native `Conf` objects cannot be cloned either. Copying only their dumped settings would silently lose callbacks
and default-topic configuration, so a raw native configuration cannot be copied for this ownership contract.

## Decision

Compose a native consumer in `ConsumerRunner`, using a fresh native `Conf` passed to a required application initializer.
Install the owned rebalance callback after initialization; preserve all other native callbacks installed there.
An application rebalance callback installed by the initializer is explicitly replaced. Reusable initializers can
also configure ordinary native consumers with their own assignment callbacks. Use a small
internal `ConsumerLoop` shared with the retained native `KafkaConsumer` compatibility subclass, without introducing
a Kafka-client abstraction, observer system or separate fake protocol.

Provide one required synchronous handler per run mode. `ConsumerBatch` snapshots native messages and captures
the highest offset plus one for each represented topic-partition. Successful handlers are followed by synchronous
commits of exactly this vector. Reject native auto-commit. Leave native auto-offset-store unchanged because vector
commits with auto-commit disabled do not depend on that store. Optional explicit `commitBatch()` requests are valid
only for the current handler's batch and execute after successful handler return, so a later handler exception
cannot acknowledge failed work. Explicit mode is not a retry queue: later partition commits advance all earlier offsets.

Separate positive poll wait, maximum batch age, record count and optional payload-plus-key byte threshold in
`BatchLimits`. Measure elapsed age from the first pending record using `hrtime()`, and round remaining poll budgets
down to native milliseconds. Drain a pending batch before admitting a record that would exceed the byte threshold;
a single oversized record is processed alone. The threshold does not bound native buffers, headers or PHP memory.

Drain pending work on a normal stop and on revocation before releasing partitions. Cooperative revocation drains
the entire current batch, including retained partitions, while they are still assigned. Use `assign()` for `range`
and `roundrobin`; use incremental assignment for `cooperative-sticky` only after checking both required methods.
Require the classic group protocol; reject unknown strategies and protocol mixtures. Release assignment in `finally`
even when processing fails.
Do not flush, retry or acknowledge failed work after an exception. A lost group generation can reject a commit and
cause already-applied side effects to replay.

Applications own stop requests, process signals and explicit close after the run. Install no signal handlers or
internal termination signal; remove the `ext-pcntl` requirement. A run is single-use, with fresh construction needed
after stop or failure. EOF and poll timeout are normal events; transport/all-brokers-down events allow native recovery;
other returned statuses are terminal. Native callback exceptions retain their cause.

## Consequences

- Managed subscribed batching can safely observe and drain rebalances. It intentionally does not accept an existing
  consumer, expose its native handle, or execute an application-owned rebalance callback.
- The native subclass preserves user callbacks and ordinary native default assignment. Legacy subscribed
  `startBatch()` now fails explicitly and must migrate to the runner; fixed manual assignment remains supported.
  Its record callbacks now execute at drain time, before its optional batch callback, through the same loop.
- `ConsumerRecords` public mutation and `getLast()` remain available but deprecated. Fresh legacy delivery objects
  and stable new batches do not disappear when the next accumulation cycle starts.
- Breaking changes include required processing, positive timing/count limits, explicit application signal handling,
  terminal-error propagation and single-use runs. Legacy native commits remain immediate and application-owned;
  only runner commit requests receive the after-handler guarantee.
- Synchronous handler and commit duration must remain below `max.poll.interval.ms` and the rebalance budget.
  Batch age cannot preempt application code. Pausing or background heartbeats alone cannot solve slow handlers.
- This is at-least-once processing. A crash, lost ownership or failed commit after application side effects can replay
  records. Applications need idempotent processing or deduplication; there is no exactly-once or external-database
  transaction guarantee.
- Native-consumer mocks cover loop control, exact vectors, deadline budgets and exception behavior. Real-broker
  tests own persisted commits, reusable native initialization and eager/cooperative revocation drains. These broker
  paths require the repository's CI Kafka stack.

Usage and migration details are in the [README consumer sections](../../README.md#consumer).
