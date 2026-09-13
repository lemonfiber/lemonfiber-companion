# The household surface

Empty, and the requirements it will hold are worth reading before anything is
put here — because two of them are about what this module must **not** grow.

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
