# Connecting the stack

What a capability is, which service fills it, and what happens when more than
one service claims the same one — and what each service is for. The code is
the `N5` half of `app-modules/kernel` and `app-modules/sdk`, drawn by
`WhatEachServiceIsFor`.

Each row says what the requirement asks and what in this repository answers it.
The spec is canonical; where this page and a requirement disagree, the
requirement is right and this page is a defect.

## What settled a capability

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N5-R1` | A contested capability is presented as a choice naming every claimant, and the app does not settle it — not by default, not by install order, not by any ordering of its own | `WhatSettledIt` has a `contested` arm that carries its claimants and offers no way to ask which of them wins. The claimants stay in the order they arrived: a sort here would be an opinion about which ought to, which is the prohibition arriving by another route |
| `N5-R3` | A choice is recorded as the operator's, and a settlement the stack made is not presented as one the operator made | `WhoSettledIt` — two cases rather than a boolean, handed out by the `chosen` arm, which cannot be entered without it |
| `N5-R12` | The app holds no copy of the capability vocabulary, and renders the names and states the core answers with | `Capability` carries a name as the core gave it and is never an enumeration of them. `HowItSettled`, `WhoSettledIt` and `HowItWasReached` are backed by the contract's own strings, and their tests assert the spelling of every case, so a word the core sends that nothing reads is a failing test rather than a blank screen |

## What reaches what

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N5-R5` | A capability nothing fills is shown as a state **with what is asking for it**, and is not presented as a fault | `Unfilled` carries both halves and cannot be built with one: a capability name alone says something is missing without saying who notices. `WhatNothingFills` carries no severity, no verdict and nothing to sort by, and has an empty form — so *everything is answered* is a value rather than an absence, which is what keeps it distinguishable from a list that could not be read |
| `N5-R6` | A by-name wiring is shown as by-name and carries the reason it was made | `HowItReaches` has two arms and `whichever()` cannot be entered without a reader for both, so a by-name wiring cannot be drawn as though it had a capability and claimants behind it. `byName()` refuses a blank reason: without one the instruction cannot be told from a choice the stack made, which is the one thing that arm exists to say it was not |

## What each service is for

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N5-R8` | A catalogue entry carries what the household is without it, and is not presented as a name and a description alone | `WhatAServiceIsFor`, which cannot be built with what the house goes without blank, beside what the service does and `HowMuchItMatters`. `Catalogues` refuses a row missing any of them as `CatalogueIsUnreadable`, so it reaches the screen as an obstacle rather than as a shorter entry. `WhatEachServiceIsFor` leads each entry with what the service does and what the house goes without, then how much that matters, and names the service after. `SeeingWhatEachServiceIsForTest` asserts the order |
| `N5-R9` | A service removed from the catalogue names what replaced it where the contract carries one | `AServiceDropped`, whose `replacement()` has an arm for what took its place and one for nothing having done so, and which refuses a blank replacement. Absent and `null` both read as nothing, which the screen says in words rather than leaving a gap; each dropped service is named by the id it was declared under, with the version that dropped it and why |
| `N1-R10` | A stack that cannot read its own description and a stack that could not be reached are different things with different remedies | The catalogue is read from the stack's manifest, and a manifest missing, written for another version, malformed, naming what this build does not know, contradicting itself, or absent from the build is answered with the problem document. `Cataloguers` hands that to `WhatARefusalMeant::inItsWords()`, the one rule every refusal in the stack's words follows, and `WhatTheCatalogueSaid::refused()` carries it. `WhatEachServiceIsFor` draws it through `RefusedInItsWords`, never as a stack that did not answer, and offers asking again only for the obstacle, because the same question is answered the same way until the manifest is put right. `CataloguingContractTest` covers each problem the stack answers the catalogue with, and `SeeingWhatEachServiceIsForTest` what is drawn (`ObstacleTest`, `WhatIsRunningTest`, `WhatIsStuckTest`, `WhatWasSaidTest`, `AStandInStackTest`, `HowTheReadingWentTest`, `UpkeepersTest`, `AdmittingContractTest`, `AskingContractTest`, `HostingContractTest`, `KeepingCurrentContractTest`, `MendingContractTest`, `OwingContractTest`, `SayingContractTest`, `SharingContractTest`, `StallingContractTest`, `SupervisingContractTest`, `WantingContractTest`, `WatchingContractTest`) |

An empty catalogue is an answer and a catalogue that could not be read is an
obstacle, and `WhatTheCatalogueSaid` keeps them apart.

## What is not here yet

Six of the thirteen are about a screen, or about an envelope nothing here
reads yet:

| Requirement | What it still needs |
|---|---|
| `N5-R2` | What the stack reaches for now and what it would reach for after — `HowItReaches` holds one side of that comparison; the other is a rehearsed substitution, which nothing reads yet |
| `N5-R4` | The `leaves_unfilled` list, which the SDK carries on a substitution and nothing here reads |
| `N5-R7` | A screen, which is where an offer could be made and so where one can be withheld |
| `N5-R10`, `N5-R11` | A plugin install, which is a different envelope again |
| `N5-R13` | An unreadable wiring, which needs a wiring to be unreadable |

## Most of them are blocked, and nothing here can unblock them

**Nothing serves the `wiring` envelope.** The type exists — `WiringEnvelope` is
generated, and the vocabulary above is read from its shape — but no endpoint
returns it. Verified in three places rather than inferred from one:

- `Lemonfiber\Sdk\Contract\Api` declares 34 endpoint constants, and no
  docblock in it names `WiringEnvelope`, `SubstitutionEnvelope` or
  `PluginsEnvelope`.
- The core's HTTP crate, `crates/lemonfiber-api`, registers no route for it.
  Neither `kind::WIRING` nor `Command::Wiring` appears anywhere in it.
- The core *can* answer it. `Command::Wiring` produces `Outcome::Wiring` and
  the contract publishes the schema, so it is reachable from the command line
  and not over the wire.

So `N5-R2`, `N5-R4`, `N5-R7`, `N5-R10`, `N5-R11` and `N5-R13` cannot be answered
here until a read exists.

This is the case `N1-R17` describes, and the rule is that the work stops rather
than going around it. An app composing a wiring view out of `/api/services` and
`/api/config` would be answering from a neighbour, and would be wrong the first
time two services claimed one capability — which is the entire subject.

The types above are still worth having: they are what a reader will read *into*
the day the read exists, and building them is what established that the envelope
carries everything except a way to ask for it.
