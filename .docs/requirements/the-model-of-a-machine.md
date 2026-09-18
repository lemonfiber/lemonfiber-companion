# The model of a machine

The types this app thinks in: what a stack is, how it is reached, what is kept
between launches, and what an action may be. The code is the `N1` half of
`app-modules/kernel`.

Each row says what the requirement asks and what in this repository answers it.
The spec is canonical; where this page and a requirement disagree, the
requirement is right and this page is a defect.

## What a stack is, and how it is told apart

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N1-R11` | Each stack's session is separate | `Admitting` takes the stack rather than an address |
| `N1-R22` | Trust is pinned to the stack rather than to where it answers | `Configured` — a machine on a new address is the same machine |
| `N1-R29` | Whether an action is supported is asked before it is offered | `Capabilities`, answered in one place so no screen works it out |
| `N1-R31` | Where two configured stacks differ in what they support, the difference is per stack | `Capabilities` carries the `StackId` |
| `N1-R39` | Which stack a screen is showing is carried, never read from somewhere shared | `Configured` |
| `N1-R35` | A launch with no stack configured is a whole screen rather than an absence something remembers | `Configured` |

## Reaching one

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N1-R8` | A session stays out of the URL | `Address`, whose only accessor is named for where the value goes |
| `N1-R12` | An unencrypted address is stated, and protection it does not have is not implied | `Address` |
| `N1-R15` | A stack address is named in the same breath as a credential and a session: never logged, never shown | `Address` |
| `N1-R16` | Every call goes through the SDK | `KeepingCurrent` and the ports beside it |
| `N1-R17` | Where the contract does not carry something, the work stops rather than approximating it from a neighbour | `WhatTheCoreDecided` |
| `N1-R13` | A refused wire version carries both halves: what arrived, and what is supported | `EnvelopeIsNotRead` |
| `N1-R14` | The wire version lives in the kernel rather than in an adapter, because the transport may change | `WireVersion` |
| `N1-R26` | A reach that waits too long is refused by name | `ReachWaitsTooLong` |
| `ARCH-R79` | — | see [what-a-machine-says.md](what-a-machine-says.md) |

## Pairing, and the session it leads to

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N1-R6` | Both roads: scanning a code, and typed entry where there is no camera or the permission was refused | `HowItWasRead` |
| `N1-R7` | The exchange trades a credential once for a session | `Admitted` |
| `N1-R18` | The fingerprint comes from the material and never from the network | `Pairing` |
| `N1-R19` | The pinned request is the one that must never reach an unverified peer | `Admitting` |
| `N1-R20` | A changed certificate becomes *this is not the machine you were introduced to* | `Fingerprint` |
| `N1-R48` | The fingerprint is the certificate that address will present, and an unencrypted address proves nothing | `Pairing` |
| `N1-R49` | Material past its moment is its own screen | `Pairing` |
| `N1-R50`, `N1-R51` | A short form to check at a glance, derived from the whole fingerprint — two constraints that pull against each other | `AtAGlance` |
| `N1-R10` | A refusal is an obstacle rather than a type of its own | `Admitted` |
| `N1-R44` | An ended session is a screen, not a port's business | `Admitting` |
| `N1-R45` | A credential expiring is not the machine changing — the pairing survives | `Interrupted` |
| `N1-R46` | An ended session is not a refused credential | `Interrupted`, whose two arms take different arguments |

## What is kept between launches

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N1-R32` | Anything kept between launches carries its shape | `Shape` |
| `N1-R33` | Older state is migrated or discarded, never read as though it were current | `Shape` |
| `N1-R34` | A discard of retained state does not take the pairing with it | `Stacks` |
| `N1-R23`, `N4-R5` | What is retained is stated, and beside it what is not | `Configured` |
| `N1-R38` | What a screen was holding when the operator left it | `Held` |
| `N1-R24` | A retained reading may open a screen and may never stand as confirmation | `Reading` |
| `N1-R9` | A value not read in this session carries when it was read | `HowLongAgo` |
| `N1-R52`, `N1-R53` | The app's identity is declared once and not taken from the builder's environment | `WhoThisAppIs` |

## What a launch can be

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N1-R36`, `N1-R37` | No network, unreachable, locked, no stack yet, and ready — told apart | `Launch` |
| `N1-R3` | A control is not hidden because something is unreachable | `Availability` |
| `N1-R30` | Unsupported and unavailable are reported as themselves | `Availability` |
| `N1-R4` | The app cannot name an action the stack did not offer | `TakingAnUpdate`, spelled once in the kernel and never at a call site |
| `N1-R28` | An indeterminate progress indicator only where the app holds nothing to show | `Showing` |

## What an action may be

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N1-R40` | An action the app could not deliver is refused rather than queued | `Attempted`; `ADR-0020` is the design |
| `N1-R41` | No retaining an undelivered action, no replaying one on reconnecting, no presenting one as pending | `Attempted` |
| `N1-R42` | An idempotency key is never serialised | `IdempotencyKey`, whose `serialize()` writes nothing |
| `N1-R65` | One reading per frame | `Asking`, which publishes one method |
| `N1-R66` | Nothing polls on the app's behalf | `HowTheOfferIsGoing` — a job that ended is where an automatic one would start |
| `N3-R13` | An identity removed from the household is a signed-out app at the next refused call | `Obstacle` |

## What is not built, and what it waits on

Two rules in `tests/Arch` are registers rather than checks: they pass while a
gap is open and go red the day it closes, so the closing announces itself
instead of being something somebody has to remember.

| Requirement | What it asks | What holds it open |
|---|---|---|
| `N1-R17` | Where the contract does not carry something the app needs, the work stops and the gap is raised rather than approximated from a neighbour | `WhatPairingMaterialCannotSayYetTest` and `WhatTheContractDoesNotCarryTest` |
| `N1-R62` | Pairing material carries an identifier that is the stack's own and survives a re-issue, a change of address and a replacement of the certificate | nothing mints pairing material yet, in any repository |
| `N1-R63` | The app decides which machine from that identifier alone, and material naming one it holds replaces rather than adds | waits on `N1-R62` |
| `N1-R64` | A re-pairing that changes the pinned fingerprint discards the session; one that does not keeps it | waits on `N1-R62` |

`WhatPairingMaterialCannotSayYet` watches `WhatPairingMaterialSays`, which is a
closed set: the material growing a field is the app growing a case there and
nowhere else. The day one lands, that rule goes red and names the work.
