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
| `N1-R13` | A wire version this build does not support is refused rather than read | `Lines`, which asserts each envelope's kind as it builds a window |
| `N1-R7` | The credential exchange happens once | `Admissions` |
| `N1-R11`, `N1-R19` | Sessions are separate per stack, and so is pinning, so the two cannot be paired up wrongly | `Upkeepers`, which fetches per stack and session |
| `N1-R42` | Every action that changes a stack carries an idempotency key; a question carries none | `Menders::agreeTo()` has one, `Menders::wouldPutRight()` does not |
| `N1-R65` | A screen reads once per frame and renders what came back | `Requests` asks for the whole household rather than narrowing per member |
| `N1-R41` | An action delivered with nothing to ask after it by has no handle | `Handles`, and `Job::named()` one layer in |
| `ARCH-R79` | A word this build has not heard of means the contract moved, and is refused rather than rendered raw | `Lines` |

| `ARCH-R56` | The contract artefact is generated from the types the server serialises, never hand-written | Nothing here generates it — this app takes it in `lemonfiber/sdk-php` and holds the pin. `sdk-drift` refuses a lock behind the client, and `WhatTheContractAccepts` refuses a stand-in payload the artefact would not accept, so a hand-edit on either side fails here rather than reaching a screen |

## What an obstacle is allowed to be

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N1-R10` | A door that is not answering and a credential that was refused are different things with different remedies, and are not flattened into one | `Admissions`; `tests/Feature/EveryObstacleSaysSomethingOfItsOwnTest.php` |
| `N2-R4` | The offer screen's payload is read or refused, never guessed at | `OfferIsUnreadable` |
| `N2-R7` | The roster's payload, likewise | `RosterIsUnreadable` |
| `N2-R10` | The log window's payload, likewise | `LineIsUnreadable` |
| `N2-R11` | The household's payload, likewise | `HouseholdIsUnreadable` |
| `N2-R14` | A value the contract did not carry is not substituted — *no reason given* is a sentence this app would have written, not one a stack said | `Households` |

## What the household asked for

| Requirement | What it asks | What keeps it |
|---|---|---|
| `D7-R3` | An estimated size is shown before a request is submitted, and *we do not know* is shown rather than guessed at | `Households` |
| `D7-R7` | Declining requires a reason, and that reason reaches the person who asked — by name | `Households` carries who asked; a declined row with no readable reason is refused rather than given one |
| `N3-R7` | What a member was told, and when, in the stack's own words | `Households` |

## What a machine is doing

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N2-R3` | A finding carries its code, its meaning and its remedy, in the words the core produced | `Reports`; a note on a passing check is the core being chatty and is dropped |
| `G4-R4` | Plain explanation leads and technical detail is available, where there is any | `Reports` |
| `N2-R6` | A repair's yes quotes the listing it was given | `Menders`, whose signature is that requirement in a parameter list: there is no way to name a repair the listing did not offer |
| `N2-R8` | What a verb costs is said before an operator confirms | `Costs` |
| `N2-R9` | The four things a stall can be, built whole — no filter to get wrong and no narrowing a screen could be tempted into | `Stalls` |
| `N2-R15`, `N2-R16` | What is installed and what is available, and a stack running a withdrawn release still has something to say about it | `Standings` |
| `N2-R18` | What happened last time an update was made, kept apart from what is on offer now | `Endings` |
| `N2-R19` | No false promise about undoing | `Standings` |
| `N2-R20` | A changelog is not something an update can be agreed about | `Standings` |
| `N2-R21` | A service the host runs is not presented as part of the stack, and no caller is given a way to spell one | `Rosters`; `Daemons` cannot hold one |
