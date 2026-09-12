# 0002: Command/query separation, without a command bus

**Status:** Accepted · **Date:** 2026-09-11

## Context

Separating reads from writes is worth having here for a specific reason: a read
can be served from what the app already holds, and a write cannot. That is the
distinction [ADR-0019](https://github.com/lemonfiber/spec) in the spec turns
into behaviour — a screen paints from a retained reading, then reaches.

CQRS usually arrives with a bus.

## Decision

Take the shape. Refuse the bus.

A class is a command or a query, never both. A query returns data; a command
returns an `Outcome` and no data. Each exposes one public method.

A dispatcher is refused because it hides the call graph. Every other rule in
this codebase points the same way — no facades, no service location, the
constructor tells the truth about what a class needs — and a bus undoes all of
them at once: the caller names a message, and what handles it is decided
somewhere the analyser cannot see. Handlers are injected directly instead, so
the arrow from caller to handler is visible to a reader, to PHPStan, and to the
architecture tests.

## Cost

No middleware pipeline, so cross-cutting concerns are composed explicitly rather
than registered once. That is more typing and a great deal less indirection.

## Wrong if

Something genuinely needs to intercept every command uniformly — an audit trail
that must not be forgotten, for instance. At that point a pipeline is worth its
opacity, and this should be revisited rather than worked around.
