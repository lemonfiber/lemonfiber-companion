# The household surface

What a member of the house sees, which is one screen: what this machine says
they can ask for, in the words the core wrote it in.

The module was empty for a long time and the reason is worth keeping, because it
is what decides the shape of what is here now. `lemonfiber` minted one token for
the run and exchanged one password for one session, and the `admission` envelope
said what the session was — `{token, until}` — and not who held it. Every caller
carrying it was the operator, so there was nobody to render a member's reading
to, and a surface built anyway would have been a screen that looked right and
was not.

## What answers the entitlement rules now

| | |
|---|---|
| `N3-R2` | What a member may do MUST be the core's answer |
| `N3-R3` | A control a member is not entitled to MUST be refused by the core if it is ever reached, and MUST NOT rely on the app having omitted it |

**Nothing here hides a control on entitlement grounds.** The app asks, the core
answers, and where the answer is *no* the app says so. A stack refusing a
request this account may not make arrives as `Obstacle::NotForThisAccount`,
which carries its own sentence and its own remedy and is told apart from a
refused credential and from a stack that did not answer — three refusals, three
screens. `tests/Contract/OwingContractTest.php` holds the reading of it and
`app-modules/sdk/tests/Internal/WhatARefusalMeantTest.php` holds the mapping.

**The distinction the surface turns on is a refusal against an empty answer.**
Both arrive with no sentences in them and they are opposite things to read: one
says there is nothing to tell you, and the other says this was not yours to ask.
Drawing a list for both is how an app passes a refusal off as an absence, and
`WhatTheyAreOwed` is two arms rather than a collection so that a screen cannot.

## What answers the allowance rules

| | |
|---|---|
| `N3-R4` | Before a member asks for something, the app states whether it needs approval and whether they have allowance left |
| `N3-R5` | A member whose allowance is spent is told before asking, with when it resets |

`household.members[].to_hand_over` carries both, written to the member rather
than about them: what happens to what they ask for, what their period has left
and when it makes room again, what is still waiting, and what was refused and
why.

**They are rendered and never composed.** The same facts are on the wire in
parts — `asking.policy`, `asking.standing`, `films.remaining`,
`television.remaining`, `asking.frees_up` — and a surface assembling its own
wording out of them would be a second voice able to disagree with the core's
about the household's rules. That is a permission model with a template around
it, which is what `N3-R2` refuses. So the port answers with `Sentences` and this
module prints them.

## What is still not built

**`N3-R1` — the application a person is given is decided by the identity that
signed in.** The admission carries `member` now, so the contract no longer holds
this open. What does is this application: `Admissions` reads the token and drops
the name, `Session` carries neither, and the secure store keeps a token per
stack and nothing about whose it is. So a resumed app cannot tell a member from
an operator, and both are given the same screens. What keeps that honest rather
than dangerous is the paragraph above: the app hides nothing, so an operator's
screen in front of a member is a screen the core refuses.

**`N3-R6` — a member's own requests carry their state in household terms.** The
app reads every member's requests for the operator and cannot tell whose is
whose, so *their own* is the half with nothing behind it. It waits on the same
thing `N3-R1` does.

Neither is registered in `tests/Arch/WhatTheContractDoesNotCarryTest.php` any
more, and that is a loss worth naming: that register goes red the day the
contract closes a gap, and the contract has closed this one. What is left is
work this repository has not done, which no register of the wire can watch.

## `N3-R9` — what a member is never shown

> A member MUST NOT be shown lifecycle controls, logs, credentials,
> diagnostics, or another member's requests.

`tests/Feature/WhatAMemberIsNeverShownTest.php` refuses a member-facing type
that holds an operator's, and it was written **before** this module held
anything — a rule added afterwards is a rule written around whatever is already
there.

Credentials are the clause that needed care, because a surface that could name
none of them could not ask a stack anything. A `Credential` is refused outright:
it is the password, it exists for one exchange, and no member screen performs
that exchange. A `Session` is not, because every screen that reads a stack is
handed one — so the line is drawn where the risk is, at keeping one or handing
one on.

## `N3-R2` — the app implements no permission model

> The app MUST NOT implement its own permission model; what a member may do MUST
> be the core's answer.

The way this is broken is never a decision. A screen needs to know whether to
draw a button, the core's answer has not arrived yet, and somebody writes the
obvious thing — a check against a role, a flag, a list of what this kind of
member may do. It works, it is fast, and it is a second permission model that
drifts from the real one the first time the core changes its mind.

Nothing in this module reads `access`, `administrator`, `disabled`, `libraries`
or `restriction`. The one question it asks has one answer and the core writes
it.
