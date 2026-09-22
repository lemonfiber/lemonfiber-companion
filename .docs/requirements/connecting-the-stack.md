# Connecting the stack

What a capability is, which service fills it, and what happens when more than
one service claims the same one. The code is the `N5` half of
`app-modules/kernel`.

Each row says what the requirement asks and what in this repository answers it.
The spec is canonical; where this page and a requirement disagree, the
requirement is right and this page is a defect.

## What settled a capability

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N5-R1` | A contested capability is presented as a choice naming every claimant, and the app does not settle it — not by default, not by install order, not by any ordering of its own | `WhatSettledIt` has a `contested` arm that carries its claimants and offers no way to ask which of them wins. The claimants stay in the order they arrived: a sort here would be an opinion about which ought to, which is the prohibition arriving by another route |
| `N5-R3` | A choice is recorded as the operator's, and a settlement the stack made is not presented as one the operator made | `WhoSettledIt` — two cases rather than a boolean, handed out by the `chosen` arm, which cannot be entered without it |
| `N5-R12` | The app holds no copy of the capability vocabulary, and renders the names and states the core answers with | `Capability` carries a name as the core gave it and is never an enumeration of them. `HowItSettled` and `WhoSettledIt` are backed by the contract's own strings, and their tests assert the spelling of every case, so a word the core sends that nothing reads is a failing test rather than a blank screen |

## What is not here yet

Nine of the thirteen are about a screen, or about a value that travels beside a
wiring rather than inside a settlement, and neither exists here yet:

| Requirement | What it still needs |
|---|---|
| `N5-R2` | What the stack reaches for now and what it would reach for after — both sides of a comparison, and there is no wiring row to compare |
| `N5-R4` | The `leaves_unfilled` list, which the SDK carries on a substitution and nothing here reads |
| `N5-R5` | The `unfilled` list that travels beside a wiring, which names what is asking for a capability nothing fills. `HowItSettled::Unfilled` is a case rather than a fault, which is half of it; what is asking is the other half |
| `N5-R6` | The by-name arm of a wiring, with the reason it was made |
| `N5-R7` | A screen, which is where an offer could be made and so where one can be withheld |
| `N5-R8`, `N5-R9` | The catalogue, which is a different envelope |
| `N5-R10`, `N5-R11` | A plugin install, which is a different envelope again |
| `N5-R13` | An unreadable wiring, which needs a wiring to be unreadable |

None of these is blocked by the contract. `WiringEnvelope` carries `unfilled`
and the by-name arm, and `SubstitutionEnvelope` carries `leaves_unfilled`, so
each is work not yet done rather than a gap to be raised.
