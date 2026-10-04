# How full a machine is

How full the stack's machine is, where the room went, each finished download
with where it stands, and stopping seeding one of them. The code is the `N12`
half of `app-modules/kernel` and `app-modules/sdk`, drawn by
`HowFullThisMachineIs` and `LettingADownloadGo`.

Each row says what the requirement asks and what in this repository answers it.
The spec is canonical; where this page and a requirement disagree, the
requirement is right and this page is a defect.

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N12-R1` | Every candidate is shown with its standing, and never imported, seeding and left alone are never flattened into one another | `ADownloadOnDisk`, built by one named constructor per `WhereADownloadStands`. The screen draws the standing on every row, and the offer to stop seeding draws the same download in the same words (`ADownloadOnDiskTest`) |
| `N12-R2` | A seeding candidate's ratio is shown | Only `ADownloadOnDisk::seeding()` takes an `ARatio`, so no other standing can carry one. `ARatio` has a case for no ratio, and `WhereTheRoomIs` and `WhatLettingItGoComesTo` read the figure the core writes for a torrent that downloaded nothing as that case, so it is said in words and never as a number (`ADownloadOnDiskTest`, `ARatioTest`) |
| `N12-R3` | What removing a candidate would cost is shown with the candidate | The stack's sentence is built into the download, not added later, and the screen draws it inside that download's entry. `SeeingHowFullThisMachineIsTest` asserts where it lands (`ADownloadOnDiskTest`) |
| `N12-R4` | Nothing is pre-selected or proposed for removal | Every download row offers stopping seeding alike, and nothing else is offered: no row is singled out, nothing is ticked, and no set is put together. `SeeingHowFullThisMachineIsTest` asserts the controls are one per download and asking again |
| `N12-R5` | Removal is agreed to explicitly, and never reachable as a single undifferentiated action | Stopping seeding is the one removal offered, one download at a time. `StoppingSeeding::stop()` takes a `WhatLettingItGoCosts`, which only the stack's offer builds, and `Releasers` sends the offer's own name as the yes and no `confirm`. The `space` cleanup of what the account names as costing nothing is not asked for: the SDK's `space` endpoint takes nothing, so this app reads the account of the disk and no more |
| `N12-R7` | Stopping seeding is shown as distinct from removing, with what stopping costs | `LettingADownloadGo`, a screen of its own reached from one download's row, says stopping seeding is its own act and draws the stack's offer before the yes: where the download stands, its ratio, what it occupies, what removing it costs and what goes with it |
| `N12-R8` | A rehearsed removal is labelled as a rehearsal and never reported as space freed | `ADownloadLetGo` carries `WhetherItWasRehearsed` off `gone.rehearsed`, and a report without it is refused. `LettingADownloadGo` heads a rehearsed report *a rehearsal: nothing has been let go* and says no room was freed; only a carried-out report says what it took. `StoppingSeedingOneDownloadTest` asserts both. The `space` cleanup is not offered, since its endpoint takes nothing, so no cleanup is ever reported |
| `N12-R9` | Nothing is removed on the app's own initiative | `LettingADownloadGo::agree()`, an operator's tap, is the only caller of `StoppingSeeding::stop()`. The screen's cadence reads the offer or the report while either is being worked on and never sends the yes, which `StoppingSeedingOneDownloadTest` asserts; the room screen itself calls nothing that removes |
| `N12-R6` | What is taking room is shown by the contract's categories, never as a file listing | `ALineOfTheAccount`, one per category, a tree carrying its name. What each would take with nothing shared is shown only where it differs. The files the stack lists as out of line are not read, for this reason, and are recorded in `WhatTheContractCarriesThatNothingReadsTest` (`ALineOfTheAccountTest`) |
| `N12-R10` | Space that could not be read is told apart from space that is comfortable | `WhereTheRoomStands::Unknown` is its own case, and a figure the stack could not read is `AnAmountOfRoom::unread()`, drawn as a sentence and never as nought. A reading this app cannot read is an obstacle, drawn as one (`AnAmountOfRoomTest`, `WhatWasMeasuredTest`) |

A volume read across a network share carries when it was taken, and the screen
dates its figures by it.

## Asked for, and not drawn yet

Clearing everything the account names as costing nothing, the confirmed `space`
action, is not offered. Its answer reports what it took the same way whether
the stack was rehearsing or not, so it could not be drawn without reporting a
rehearsal as room freed, and it takes a bare `confirm` rather than the name of
the reading it was given for. It is removed at the machine.
