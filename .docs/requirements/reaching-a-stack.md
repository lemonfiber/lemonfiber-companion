# Reaching a stack

Everything this app asks a machine for, and everything it reads back. The code
is `app-modules/sdk`, which is the only module allowed to name
[`sdk-php`](https://github.com/lemonfiber/sdk-php).

Each row says what the requirement asks and what in this repository answers it.
The spec is canonical; where this page and a requirement disagree, the
requirement is right and this page is a defect.

## How a call is made at all

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N1-R16` | Every call to a stack goes through the SDK — no URL built here, no envelope parsed that the SDK did not hand over | `Admissions`, `Requests` and every reader beside them; `tests/Arch/NothingOpensAConnectionByHandTest.php` |
| `N1-R20` | One file may open a connection, so the certificate pin has a single home | `PinnedClients` and `PinnedDoors`; `tests/Feature/NothingReachesAStackUnpinnedTest.php` |
| `N1-R18` | The pinned fingerprint comes from the pairing material and never from the network | `PinnedClients` — a fingerprint learned from the connection it is meant to validate proves nothing |
| `N1-R26` | Every call to a stack waits at most the app's bound, every attempt at it included | `HowLongACallWaits::ordinarily()` hands `Timeout::ordinary()` to `Client::pinnedAt()` and `Admission::at()`, and the SDK holds every attempt at a call to the wait the first began; `app-modules/sdk/tests/Api/PinnedClientsTest.php` (`TimeoutTest`) |
| `N1-R13` | A wire version this build does not support is refused rather than read | `Lines`, which asserts each envelope's kind as it builds a window (`WireVersionTest`, `LinesTest`, `ProblemsTest`, `ReportsTest`, `WireTest`, `EveryWireValueIsACaseTest`) |
| `ARCH-R55` | An `api_version` mismatch is refused plainly, naming both versions, rather than rendering a partial view | `WhatTheReachMet::versions()`, which every adapter's mismatch reaches through `Clients::whatStoodInTheWay()`, carries both numbers on `Obstacle::versionsDisagree()`; the screen names them and nothing the envelope held is drawn. `ClientsContractTest` holds every set of clients to it |
| `ARCH-R62` | A resuming client sends the last event id it saw as `Last-Event-ID` | `Listeners`, `Narrators` and `StartLines` keep where each stack's stream left off and open the next one after it; `HearingContractTest`, `HearingTheWalkContractTest` and `HearingTheStartContractTest` read the header off what was sent |
| `N1-R7` | The credential exchange happens once | `Admissions` (`CredentialTest`, `AdmittingContractTest`, `SecureStorageContractTest`, `EveryScreenIsWalkableWithNoStackRunningTest`, `SigningIntoAStackTest`, `TheFirstFrameIsOfferedTest`) |
| `N1-R11`, `N1-R19` | Sessions are separate per stack, and so is pinning, so the two cannot be paired up wrongly | `Upkeepers`, which fetches per stack and session (`RecognisedTest`, `PinnedClientsTest`) |
| `N1-R42` | Every action carries an idempotency key minted where it is sent, the ones that only describe what they would do included | Every `act()` and `repair()` in `sdk`; `EveryActionNamesItsAttemptTest` reads them all (`IdempotencyKeyTest`, `MendersTest`, `SupervisorsTest`, `AnActionIsNeverHeldTest`) |
| `N1-R65` | A screen reads once per frame and renders what came back | `Requests` asks for the whole household rather than narrowing per member (`AskingContractTest`, `OwingContractTest`, `WantingContractTest`, `SeeingHowAStackIsTest`, `SeeingHowCurrentAStackIsTest`, `SeeingWhatStoppedComingInTest`, `SeeingWhatTheHouseholdAskedForTest`, `SeeingWhatWouldBePutRightTest`, `SeeingWhatYouAreOwedTest`, `SeeingWhatYouCanWatchTest`) |
| `N1-R41` | An action delivered with nothing to ask after it by has no handle | `Handles`, and `Job::named()` one layer in (`AttemptedTest`, `MendingContractTest`, `SeeingWhatWouldBePutRightTest`, `AnActionIsNeverHeldTest`) |
| `ARCH-R79` | A word this build has not heard of means the contract moved, and is refused rather than rendered raw | `Lines` (`AvailabilityTest`, `CapabilitiesTest`) |

| `ARCH-R56` | The contract artefact is generated from the types the server serialises, never hand-written | Nothing here generates it — this app takes it in `lemonfiber/sdk-php` and holds the pin. `sdk-drift` refuses a lock behind the client, and `WhatTheContractAccepts` refuses a stand-in payload the artefact would not accept, so a hand-edit on either side fails here rather than reaching a screen |

## What an obstacle is allowed to be

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N1-R10` | A door that is not answering and a credential that was refused are different things with different remedies, and are not flattened into one | `Admissions`; `tests/Feature/EveryObstacleSaysSomethingOfItsOwnTest.php` |
| `N1-R10` | A stack that did not answer and a phone with no network are told apart on every reach, not only at launch | `ClientsThatAskTheDevice`, bound at `Clients`, asks `Networking` whenever a reach met silence; every adapter's silence goes through `Clients::whatStoodInTheWay()` |
| `N4-R17` | A refused local-network permission is told apart from a stack that did not answer, on every reach | `ClientsThatAskTheDevice` asks `TheLocalNetwork`, which `PlatformLocalNetwork` answers through the bridge's probe; `TheLocalNetworkContractTest` |
| `N4-R17` | The way to grant a refused local-network permission is offered | Every screen that draws an obstacle offers *Open Settings* beside asking again where `Obstacle::isPutRightInTheAppsSettings()` says so, through `OffersTheAppsSettings` and `TheAppsSettings`, which `PlatformAppsSettings` answers through `Lemonfiber.Settings.Open`; a phone that would not open the page is said to have refused, beside the button; `TheAppsSettingsContractTest`, `SeeingWhatIsRunningTest` |
| `ARCH-R135` | Work stopped because other work held the stack is said to be that, with the same request offered once it is done | `WhatARefusalMeant` reads a `409` the stack sends without a code this build knows as `StackIsBusy`, never as the machine failing. Where it met an action (a verb, a repair's yes, taking an update, moving in, or asking what a repair or a move would come to), `x-operator::try-again` offers *Try again* beside it, read off `HowTheReadingWent::wasHeldByOtherWork()`, and the screen's `tryAgain()` sends what was agreed to, unchanged, under a new key, only on the tap. Any other obstacle offers no *Try again*, since the stack may have acted on that request |
| `N2-R4` | The offer screen's payload is read or refused, never guessed at | `OfferIsUnreadable` (`OfferTest`, `RepairTest`, `RepairsTest`, `OffersTest`, `MendingContractTest`, `SeeingHowAStackIsTest`, `SeeingWhatWouldBePutRightTest`, `TypesThatMustNotMeetTest`) |
| `N2-R7` | The roster's payload, likewise | `RosterIsUnreadable` (`AgreedToTest`, `DaemonTest`, `DaemonsTest`, `FormTest`, `HowAServiceRunsTest`, `HowMuchItMattersTest`, `HowTheStackIsRunningTest`, `WhatIsRunningTest`, `RepertoiresTest`, `SupervisorsTest`, `MendingContractTest`, `SupervisingContractTest`, `DecidingWhatToDoWithOneThingTest`, `SeeingWhatThisStackRunsTest`, `SeeingWhatWouldBePutRightTest`, `TheAppOpensOnlyTheseDoorsTest`) |
| `N2-R10` | The log window's payload, likewise | `LineIsUnreadable` (`AboutWhatTest`, `HowManyLinesTest`, `LookingForTest`, `SaidTest`, `ScrollbackTest`, `ServiceIdTest`, `StreamTest`, `WhatWasSaidTest`, `LinesTest`, `SayingContractTest`, `ReadingWhatAServiceSaidTest`, `SeeingHowAStackIsTest`) |
| `N2-R11` | The household's payload, likewise | `HouseholdIsUnreadable` (`DecidedTest`, `RequestedTest`, `WantedTest`, `HouseholdsTest`, `WantingContractTest`, `SeeingHowAStackIsTest`, `SeeingWhatTheHouseholdAskedForTest`) |
| `N2-R14` | A value the contract did not carry is not substituted — *no reason given* is a sentence this app would have written, not one a stack said | `Households` (`UpkeepersTest`, `WhatTheContractDoesNotCarryTest`) |

## What the household asked for

| Requirement | What it asks | What keeps it |
|---|---|---|
| `D7-R3` | An estimated size is shown before a request is submitted, and *we do not know* is shown rather than guessed at | `Households` (`HowBigTest`, `SizeTest`, `WantedTest`, `HouseholdsTest`, `SeeingWhatTheHouseholdAskedForTest`) |
| `D7-R7` | Declining requires a reason, and that reason reaches the person who asked — by name | `Households` carries who asked; a declined row with no readable reason is refused rather than given one (`DecidedTest`, `TurnedDownTest`, `WantedTest`, `HouseholdsTest`, `WantingContractTest`, `SeeingWhatTheHouseholdAskedForTest`) |
| `N3-R7` | What a member was told, and when, in the stack's own words | `Households` (`TurnedDownTest`, `WantedTest`, `HouseholdsTest`, `SeeingWhatTheHouseholdAskedForTest`) |

## What a machine is doing

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N2-R3` | A finding carries its meaning and its remedy, in the words the core produced, and its code one step away | `Reports`; a note on a passing check is the core being chatty and is dropped (`FindingTest`, `ReportsTest`, `SeeingHowAStackIsTest`) |
| `G4-R4` | Plain explanation leads and technical detail is available, where there is any | `Reports` (`FindingTest`, `ReportsTest`, `SeeingHowAStackIsTest`) |
| `N2-R6` | A repair's yes quotes the listing it was given | `Menders`, whose signature is that requirement in a parameter list: there is no way to name a repair the listing did not offer (`ConfirmedTest`, `OfferTest`, `RepairsTest`, `MendingContractTest`, `SeeingWhatWouldBePutRightTest`) |
| `N2-R8` | What a verb costs is said before an operator confirms | `Costs` (`AgreedToTest`, `DaemonTest`, `HowAServiceRunsTest`, `HowMuchItMattersTest`, `WhatLeansOnItTest`, `WhatToDoWithItTest`, `SupervisingContractTest`, `DecidingWhatToDoWithOneThingTest`) |
| `N2-R9` | The four things a stall can be, built whole — no filter to get wrong and no narrowing a screen could be tempted into | `Stalls` (`HowMuchIsShownTest`, `StageTest`, `StalledTest`, `StuckTest`, `WhatIsStuckTest`, `StoppagesTest`, `StallingContractTest`, `EverythingN2R9NamesIsReachableTest`, `SeeingHowAStackIsTest`, `SeeingWhatStoppedComingInTest`) |
| `N2-R15`, `N2-R16` | Whether an update is available is read off the pins, the release history is read as history, and a stack running a withdrawn release still has something to say about it | `Standings`, with the `changelog` block read by `Changelogs` (`StandingsTest`, `KeepingCurrentContractTest`, `SeeingHowCurrentAStackIsTest`) |
| `N2-R18` | How an update taken went, read off the update's own report and kept apart from what is on offer now | `Upkeepers::whatBecameOf()`, which follows the handle taking it answered, and `Endings` (`EndingsTest`, `StandingsTest`, `NotArrivedFirstTest`, `KeepingCurrentContractTest`, `SeeingHowCurrentAStackIsTest`) |
| `N2-R19` | No false promise about undoing | `Standings` (`EndingsTest`, `KeepingCurrentContractTest`, `SeeingHowCurrentAStackIsTest`) |
| `N2-R20` | A changelog is not something an update can be agreed about | `Standings`, which reads availability from the top-level `state` and never from `changelog.state` (`SeeingHowCurrentAStackIsTest`, `TypesThatMustNotMeetTest`) |
| `N2-R21` | A service the host runs is not presented as part of the stack, and no caller is given a way to spell one | `Rosters`; `Daemons` cannot hold one (`RostersTest`, `SeeingWhatElseIsRunningTest`) |
