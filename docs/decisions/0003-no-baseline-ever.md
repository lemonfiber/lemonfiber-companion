# 0003: There is no baseline, and there will not be one

**Status:** Accepted · **Date:** 2026-09-11

## Context

PHPStan runs at `level: max` with strict rules, larastan, ergebnis, shipmonk,
type coverage at 100% on five axes, cognitive complexity caps, and a large
`disallowed-calls` configuration. That is strict enough that somebody will
eventually want to defer a failure.

The mechanism for deferring is a baseline: a file listing today's errors so they
stop being reported.

## Decision

No baseline file, no `ignoreErrors` section, and `reportUnmatchedIgnoredErrors`
is on so that a stale ignore fails rather than rots.

A baseline converts today's failures into tomorrow's silence. It is written
once, in a hurry, by someone unblocking themselves — and then read by nobody,
because a file of accepted errors has no audience. Six months on it is a list of
things the analyser was told to stop mentioning, and nobody can say which of
them were decisions.

When the analyser is wrong, teach it: a stub, or a narrower `allowIn` path on
the rule. When it is right, fix the code. Both leave something a reader can
evaluate; a baseline entry leaves a line number.

`phpstan/TestCall.stub` exists for the first case and is the shape this
codebase prefers.

## Cost

A rule that turns out to be slightly wrong blocks work until it is scoped
correctly, rather than until someone gets around to it. That is the intended
trade and it is occasionally annoying.

## Wrong if

A rule set we do not control ships a change producing hundreds of failures at
once. Even then the answer is to pin the previous version and fix them
deliberately, not to record them.
