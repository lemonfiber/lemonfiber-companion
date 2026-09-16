# The household surface

Empty, and the first thing to know is that it is **blocked** rather than
unstarted. The requirements below are worth reading anyway, because two of them
are about what this module must not grow — but nothing here can be built until
the surface says who is asking.

## The wire does not say who signed in

`lemonfiber` mints one token for the run and exchanges one password for one
session, and the `admission` envelope says what the session is — `{token,
until}` — and not who holds it. Every caller carrying it is the operator.

That takes three requirements at once, and holds three more behind them:

| | |
|---|---|
| `N3-R1` | The application a person is given MUST be decided by the identity that signed in |
| `N3-R2` | What a member may do MUST be the core's answer |
| `N3-R3` | A control a member is not entitled to MUST be refused by the core if it is ever reached, and MUST NOT rely on the app having omitted it |

**The wire does carry what a member may do.** `household.members[].access`
has `administrator`, `disabled`, `libraries` and `restriction`, and a reader
finding that could reasonably conclude the paragraph above is stale. It is not:
what is missing is not the entitlement but the *subject*. An app holding the
access list can only match it to a person by deciding for itself which person is
looking, and a control hidden on that basis is hidden by the app.

`N3-R3` is the one that makes this a block rather than a slow start. A member
surface built on a single operator token would be safe exactly to the extent
that it remembered to leave controls out — and *the app omitted it* is the one
answer that requirement refuses. Building it would produce a screen that looks
right and is not, which is worse than the empty directory.

`N3-R4` and `N3-R5` are held on the same shelf for a narrower reason: nothing on
the wire says what a member has left of an allowance or when it resets.

**Three more wait on the same subject, and are named so the day it arrives names
all of them.** `N3-R6` has a member's own requests carry their state in
household terms — the app already reads every member's requests for the operator
(`N2-R11`) and cannot tell whose is whose, so *their own* is the half with
nothing behind it. `N3-R10` is answered in half: a member is not shown the fault,
which is a rule about types and is kept in
`tests/Feature/WhatAMemberIsNeverShownTest.php`, and is not yet told that it did
not work and that the operator has been told, which needs somebody to tell.

`N3-R9` is the exception and is answered now rather than waiting: that suite
refuses a member-facing type that holds an operator's, written **before** the
module exists on purpose. A rule added afterwards is a rule written around
whatever is already there.

All of them are in `tests/Arch/WhatTheContractDoesNotCarryTest.php`, which reads
the generated contract and **fails the day any of them arrives** — so this
paragraph stops being true in a run rather than in somebody's memory.

## `N3-R2` — the app implements no permission model

> The app MUST NOT implement its own permission model; what a member may do MUST
> be the core's answer.

The way this is broken is never a decision. A screen needs to know whether to
draw a button, the core's answer has not arrived yet, and somebody writes the
obvious thing — a check against a role, a flag, a list of what this kind of
member may do. It works, it is fast, and it is a second permission model that
drifts from the real one the first time the core changes its mind.

So there is no type here that answers whether a member may do something. When
that answer is needed it is read off what the core sent, never computed.

## `N3-R11` — no second copy of a parental limit

> Parental limits MUST be rendered from the core's answer, and the app MUST NOT
> hold a second copy of them.

The same shape, and worse in the same way: a limit cached here and a limit
enforced there disagree eventually, and the disagreement shows up as a child
being told they may ask for something the core then refuses — or, the other way
round, being told they may not ask for something they are entitled to.

## Why neither is a gate yet

Both would need a rule that reads prose — "does anything here look like a
permission check", "does anything here look like a stored allowance" — and this
repository has already paid for that once. The `N1-R17` checker matched the
comments *explaining* the rule, in `Finding` and `Wire`, and had to be deleted;
a rule that fires on its own documentation is a rule somebody switches off, and
the switching off takes the real coverage with it. `Vocabulary` reads tokens
rather than text for the same reason.

What can be checked mechanically will be, the moment there is a shape to check
rather than a sentence to match. Until then this file is where the requirement
is, in front of whoever adds the first class.

## `N3-R8` is held, and not from here

> The app MUST NOT play media; it MUST hand off to a household client.

Enforced in `tests/Arch/NothingPlaysMediaHereTest.php`, which reads the platform
sources rather than the PHP — playing media is a platform capability and this
side can only ask for it, so a rule reading `app-modules` would be looking where
the thing it forbids cannot happen. Eleven symbols across Android and iOS.

Worth knowing before the first screen is written here: the temptation is not to
build a player, it is that a member taps a title, there is nowhere to send them
yet, and playing it right there is four lines. What that costs is a second
implementation of transcoding, resume points, subtitles and what a member may
watch — which is `N3-R11`'s argument about limits, applied to playback.

## `N3-R12` is already held

> While the stack is unreachable, asking for something new MUST be declined
> rather than queued.

That is `N1-R41` for a different surface, and it is enforced in
`tests/Arch/AnActionIsNeverHeldTest.php` — nothing holds a collection of actions
waiting to be sent, and `Attempted` has no arm meaning "pending". Do not write a
second check here. One fact in two places is one fact that will be corrected in
one of them.
