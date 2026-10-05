# Connecting the stack

What a capability is, which service fills it, and what happens when more than
one service claims the same one — and what each service is for. The code is
the `N5` half of `app-modules/kernel` and `app-modules/sdk`, drawn by
`HowTheServicesAreWired` and `WhatEachServiceIsFor`.

Each row says what the requirement asks and what in this repository answers it.
The spec is canonical; where this page and a requirement disagree, the
requirement is right and this page is a defect.

## What answers what

The Connections screen (`HowTheServicesAreWired`) opens on what answers what,
above the services and the run. `Linkers` reads `GET /api/wiring` through the
SDK into `TheLinks`, read fresh each time the screen opens and each time it is
asked again. The stack's event stream, which the screen holds through
`HoldsItsStacksStream`, carries the same envelope as a `wiring` event when the
screen starts listening and whenever the wiring changes; `Listeners` reads it
beside the health summary and the newest, and the screen draws it in place of
what it last read (`HearingContractTest`, `WiringTheServicesTest`). Neither is
kept on the phone, and `HowTheLinksRead` draws each
link as the service that asked and the capability, how it settled, and every
claimant with where it came from in the line every service's origin is drawn
in (`WiringTheServicesTest`, `LinkingContractTest`).

Where two or more services claim a capability, the row offers each claimant
that does not answer it already. A tap asks `wiring-fill` through `Fillers` as
a rehearsal with no offer, which writes nothing, and the screen draws what the
stack worked out: what answers the capability now, what would answer it after,
every service that asks for it, what the choice would leave unfilled, and a
field for an optional reason. Only *Make this the choice* writes, and it sends
the reading's `agreement` back as the `offer` with the reason, so a reading
that moved in between is refused by the stack, worked out again and drawn in
its place. The reading and the reason live on the screen while it is open and
are kept nowhere on the phone (`ChoosingWhoAnswersTest`,
`ChoosingAFillerContractTest`).

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N5-R1` | A contested capability is presented as a choice naming every claimant, and the app does not settle it — not by default, not by install order, not by any ordering of its own | `WhatSettledIt` has a `contested` arm that carries its claimants and offers no way to ask which of them wins. The claimants stay in the order they arrived: a sort here would be an opinion about which ought to, which is the prohibition arriving by another route. The screen draws a contest as a notice naming every candidate in the contest's own order with where it came from, and offers each of them, in that order, as a choice with none picked. A choice is asked only for a pair the screen offered in what it last read (`HowTheLinksReadTest`, `WiringTheServicesTest`, `ChoosingWhoAnswersTest`) |
| `N5-R2` | Before the choice is made, the app states what the stack reaches for now and what it would reach for after | `AFill` carries what answers the capability now, which is none where nothing does, and the service that would answer it after. `Substitutions` reads both from the `substitution` envelope and refuses one missing what would answer after. The screen draws *Reaches now* or *Reaches nothing now* and *Would reach* before *Make this the choice* is offered (`ChoosingAFillerContractTest`, `ChoosingWhoAnswersTest`) |
| `N5-R3` | A choice is recorded as the operator's, and a settlement the stack made is not presented as one the operator made | `WhoSettledIt` — two cases rather than a boolean, handed out by the `chosen` arm, which cannot be entered without it. `Links` reads `whose` into it and refuses a word it has no case for, and the screen says *You chose* for the operator's and *The stack chose* for the stack's, never one for the other (`LinkingContractTest`, `WiringTheServicesTest`) |
| `N5-R4` | A substitution states what it would leave unfilled before it is agreed to, naming each service and the capability it would lose | `AFill` carries `leaves_unfilled` as `WhatNothingFills`, each `Unfilled` naming the service and the capability. `Substitutions` refuses an envelope without the list, or with a row missing either half, as `SubstitutionIsUnreadable`, so a shorter list never reaches the screen as a smaller cost. The screen names each loss, or says nothing is left unanswered, before the yes (`ChoosingAFillerContractTest`, `ChoosingWhoAnswersTest`) |
| `N5-R5` | A capability nothing fills is shown as a state **with what is asking for it**, and is not presented as a fault | `Unfilled` carries both halves and cannot be built with one: a capability name alone says something is missing without saying who notices. `WhatNothingFills` carries no severity, no verdict and nothing to sort by, and has an empty form — so *everything is answered* is a value rather than an absence, which is what keeps it distinguishable from a list that could not be read. On the screen an unfilled ask is headed by the service that asked and says *Nothing answers this*, in no tone (`WiringTheServicesTest`) |
| `N5-R6` | A by-name wiring is shown as by-name and carries the reason it was made | `HowItReaches` has two arms and `whichever()` cannot be entered without a reader for both, so a by-name wiring cannot be drawn as though it had a capability and claimants behind it. `byName()` refuses a blank reason: without one the instruction cannot be told from a choice the stack made, which is the one thing that arm exists to say it was not. The screen says it is wired by name, not by what it does, with the reason, and offers nothing on it (`WiringTheServicesTest`) |
| `N5-R7` | The app does not offer to create a wiring a plugin is forbidden to create | Nothing here offers a wiring by name. A choice of filler names a capability and one of the claimants the stack listed for it, and the stack decides whether it can be made (`ChoosingWhoAnswersTest`) |
| `N5-R12` | The app holds no copy of the capability vocabulary, and renders the names and states the core answers with | `Capability` carries a name as the core gave it and is never an enumeration of them. `HowItSettled`, `WhoSettledIt` and `HowItWasReached` are backed by the contract's own strings, and their tests assert the spelling of every case, so a word the core sends that nothing reads is a failing test rather than a blank screen. `Links` reads each tagged arm on the word it names and refuses one it has no case for (`LinkingContractTest`) |
| `N5-R13` | Where the wiring cannot be read, the app says so and does not render the stack as settled | A wiring the stack refuses with a problem document is `WhatTheLinksSaid::refused()`, in the stack's words, and an envelope this app cannot read is an obstacle; neither is an empty `TheLinks`. The screen says *What answers what could not be read* above the stack's words or what stood in the way, and draws no link and not *The stack asks nothing of its services* (`LinkingContractTest`, `EveryAdapterAnswersWhatItCannotReadTest`, `WiringTheServicesTest`) |

## What each service is for

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N5-R8` | A catalogue entry carries what the household is without it, and is not presented as a name and a description alone | `WhatAServiceIsFor`, which cannot be built with what the house goes without blank, beside what the service does and `HowMuchItMatters`. `Catalogues` refuses a row missing any of them as `CatalogueIsUnreadable`, so it reaches the screen as an obstacle rather than as a shorter entry. `WhatEachServiceIsFor` leads each entry with what the service does and what the house goes without, then how much that matters, and names the service after. `SeeingWhatEachServiceIsForTest` asserts the order |
| `N5-R9` | A service removed from the catalogue names what replaced it where the contract carries one | `AServiceDropped`, whose `replacement()` has an arm for what took its place and one for nothing having done so, and which refuses a blank replacement. Absent and `null` both read as nothing, which the screen says in words rather than leaving a gap; each dropped service is named by the id it was declared under, with the version that dropped it and why |
| `N1-R10` | A stack that cannot read its own description and a stack that could not be reached are different things with different remedies | The catalogue is read from the stack's manifest, and a manifest missing, written for another version, malformed, naming what this build does not know, contradicting itself, or absent from the build is answered with the problem document. `Cataloguers` hands that to `WhatARefusalMeant::inItsWords()`, the one rule every refusal in the stack's words follows, and `WhatTheCatalogueSaid::refused()` carries it. `WhatEachServiceIsFor` draws it through `RefusedInItsWords`, never as a stack that did not answer, and offers asking again only for the obstacle, because the same question is answered the same way until the manifest is put right. `CataloguingContractTest` covers each problem the stack answers the catalogue with, and `SeeingWhatEachServiceIsForTest` what is drawn (`ObstacleTest`, `WhatIsRunningTest`, `WhatIsStuckTest`, `WhatWasSaidTest`, `AStandInStackTest`, `HowTheReadingWentTest`, `UpkeepersTest`, `AdmittingContractTest`, `AskingContractTest`, `HostingContractTest`, `KeepingCurrentContractTest`, `MendingContractTest`, `OwingContractTest`, `SayingContractTest`, `SharingContractTest`, `StallingContractTest`, `SupervisingContractTest`, `WantingContractTest`, `WatchingContractTest`, `DecidingWhatToDoWithOneThingTest`, `EveryObstacleSaysSomethingOfItsOwnTest`, `EveryScreenIsWalkableWithNoStackRunningTest`, `ReadingWhatAServiceSaidTest`, `SeeingHowAStackIsTest`, `SeeingHowCurrentAStackIsTest`, `SeeingWhatKeepsRunningTest`, `SeeingWhatStoppedComingInTest`, `SeeingWhatThisStackRunsTest`, `SeeingWhatWouldBePutRightTest`, `SeeingWhatYouCanWatchTest`, `SigningIntoAStackTest`, `TheFirstFrameIsOfferedTest`, `TheWholeWireAnswersWithNoStackOnItTest`) |

An empty catalogue is an answer and a catalogue that could not be read is an
obstacle, and `WhatTheCatalogueSaid` keeps them apart.

## What is not here yet

Two of the thirteen are about a plugin install, and no screen here offers one:

| Requirement | What it still needs |
|---|---|
| `N5-R10`, `N5-R11` | A plugin install, which is a different envelope again |
