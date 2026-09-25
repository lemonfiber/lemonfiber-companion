# How full a machine is

How full the stack's machine is, where the room went, and each finished
download with where it stands. The code is the `N12` half of
`app-modules/kernel` and `app-modules/sdk`, drawn by `HowFullThisMachineIs`.

Each row says what the requirement asks and what in this repository answers it.
The spec is canonical; where this page and a requirement disagree, the
requirement is right and this page is a defect.

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N12-R1` | Every candidate is shown with its standing, and never imported, seeding and left alone are never flattened into one another | `ADownloadOnDisk`, built by one named constructor per `WhereADownloadStands`. The screen draws the standing on every row |
| `N12-R2` | A seeding candidate's ratio is shown | Only `ADownloadOnDisk::seeding()` takes an `ARatio`, so no other standing can carry one. `ARatio` has a case for no ratio, and `WhereTheRoomIs` reads the figure the core writes for a torrent that downloaded nothing as that case, so it is said in words and never as a number |
| `N12-R3` | What removing a candidate would cost is shown with the candidate | The stack's sentence is built into the download, not added later, and the screen draws it inside that download's entry. `SeeingHowFullThisMachineIsTest` asserts where it lands |
| `N12-R4` | Nothing is pre-selected or proposed for removal | The screen offers asking again and nothing else |
| `N12-R6` | What is taking room is shown by the contract's categories, never as a file listing | `ALineOfTheAccount`, one per category, a tree carrying its name. What each would take with nothing shared is shown only where it differs. The files the stack lists as out of line are not read, for this reason, and are recorded in `WhatTheContractCarriesThatNothingReadsTest` |
| `N12-R9` | Nothing is removed on the app's own initiative | Nothing here calls anything that removes. The `space` reading is the only call the screen makes |
| `N12-R10` | Space that could not be read is told apart from space that is comfortable | `WhereTheRoomStands::Unknown` is its own case, and a figure the stack could not read is `AnAmountOfRoom::unread()`, drawn as a sentence and never as nought. A reading this app cannot read is an obstacle, drawn as one |

A volume read across a network share carries when it was taken, and the screen
dates its figures by it.

## Asked for, and not drawn yet

`N12-R5`, `N12-R7` and `N12-R8` are about removing and stopping seeding: each
is an act, and this app offers none of them.
