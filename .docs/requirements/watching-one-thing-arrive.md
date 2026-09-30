# Watching one thing arrive

The first acquisition, narrated end to end: a walkthrough started from the
phone, followed while it runs, and its record drawn once it finishes, with where
it stopped and why, what the import did, and what to do next. The code is the
walkthrough half of `N15` and of `N24` in `app-modules/kernel` and
`app-modules/sdk`, with the handle of a walk left running kept by
`app-modules/vault`, drawn by `WatchingOneArrive` and reached from the home
screen of a stack.

Each row says what the requirement asks and what in this repository answers it.
The spec is canonical; where this page and a requirement disagree, the
requirement is right and this page is a defect.

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N15-R6` | A walkthrough's lines are carried as given, never reconstructed or reordered | `Walkthroughs` reads every line in the order the stack sent it into `TheLinesItSaid`, and `ALineItSaid` holds the sentence and its detail exactly as said. `HowAWalkthroughReads` hands them on in that order, and the screen draws them whole under one heading, as a record: a walk that finished while nobody was looking reads the same as one that was watched |
| `N15-R7` | Where a walkthrough names what to do next, that is shown | The handover is `WhatComesNext`, read in the stack's order, and the screen draws a sentence for each thing it names; where to watch leads to `WhichAppToWatchOn`. A walk that names nothing to do next draws no handover |
| `N15-R8` | *Already here* is its own outcome, never a search that found nothing | `AWalkthrough::wasAlreadyHere()` carries the stack's own field, and the screen says it first, in a sentence of its own. Why a walk stopped is `WhyTheWalkthroughStopped`, a separate reading, and a search that matched nothing is one of its ten causes |
| `N15-R9` | The app defines no word the glossary does not carry | Before a walk the screen asks the stack for its glossary and draws the road a walk takes, each `WalkthroughStep` in the contract's order. A step is drawn as the glossary's word for it where the glossary carries it, explained in place by `ShowsWhatItsWordsMean`, and as it came where it does not. `WalkthroughStep` keys no sentence of its own |
| `N15-R5` | Where the contract gives a job's stage, the stage is shown, and a progress indicator never stands in for it | The stack says each step of a walk on its event stream as it happens, and `HearsWhereTheWalkIs` holds the stream while the walk runs: `Narrators` hands over the last `step` said since the screen last looked, read by `Walkthroughs::said()` into the line a record would carry, and `WhatTheWalkSaidSoFar` keeps the newest, backwards or not. The screen draws it as the glossary's word for the stage, explained in place, with what the walk said there. It draws no bar |
| `N15-R10` | A stage that could not be read is told apart from idle | Before a stage is heard the screen says the stack has not said one yet, and so does a walk found again before the first wake opens the stream, and a screen back in front of somebody before the wake that opens it again. A stream that could not be opened or read, closed, or went quiet past the contract's bound says the stage could not be heard, and when it listens again; a stage heard before either is drawn as last heard, with when, and never as where the walk is now. `WhatTheWalkSaidSoFar::hasBroken()` is what tells a break from not listening yet |
| `N1-R27` | A screen whose content changes while open refreshes on a cadence it declares | While the walk runs, `WatchingOneArrive::whileItRuns()` asks after the handle at `HowOftenAScreenLooks::WhileWorkRuns`. A finished record is never asked after again |
| `N24-R6` | A walkthrough can be started at any time, for an item named or one the stack picks, and a stack that acquires nothing is offered the walk it gives | The screen is reached from `HowThisStackIs`, and its walk box is drawn whenever no walk is running. `WhatToWalk::called()` takes what was typed, and a blank box leaves the choice to the stack. The suggestions a walk answers with can each be started with a tap through `WatchingOneArrive::walkSuggested()`. The stack decides which walk it is: `WhichWalk::LibraryOnly` is the walk for a stack that fetches nothing, and it is drawn with a sentence of its own |
| `N24-R7` | Leaving a walkthrough does not cancel it, the app says so, and returning shows where it got to | Nothing on the screen stops a walk. While one runs the screen says that leaving does not stop it. The handle is kept on the device per stack and `KindOfWork` by `WorkLeftRunning`, which `PlatformWorkLeftRunning` writes to the platform's store, and `WatchingOneArrive::mount()` follows it when the screen is opened again: still running, the record, or that the stack no longer has an outcome. Where the device would not keep the handle, `WhatAReturnFinds` says so and the screen says coming back will not find the walk. A finished record is kept for the next return until another walk starts, and a walk the stack no longer knows is let go of. The record draws `AWalkthrough::wentOnInTheBackground()` and `WhereTheWalkthroughIs::Abandoned`, each with its own sentence |
| `N24-R8` | A stopped walkthrough shows the step, the reason, the remedy and the logs together, and *nothing matched* is never the indexers failing | `WhereItStopped` carries all four, and the screen draws them together with the logs inline, or says the services said nothing. `WhyTheWalkthroughStopped` keeps `NothingMatched` apart from `IndexersFailed`, each with its own sentence |
| `N24-R9` | An import that copied rather than linked says so | `HowTheImportLinked::Copied` has its own sentence, drawn with the record: the file now takes its space twice, and every import will do the same |
| `N1-R65`, `N1-R66` | One reading per frame, and beyond it only a declared cadence or an operator's act | The stream is opened by the operator's act of starting a walk, before the walk is asked for, so its first step is heard. A walk found again when the screen is opened is listened to from the first wake that asks after it, so opening the screen waits on nothing. It is looked at on the wakes that ask after the handle, which sends nothing; opened again after a break on `HowOftenAScreenLooks::AfterABreak`; and let go of when the walk is over, when nobody can see the screen, and when it is left |

A glossary that cannot be had before a walk is what stood in the way, drawn as
such.

Starting one names the title typed, or nothing, which the stack reads as
*suggest something likely to work*; the suggestions it answers with can each be
started with a tap. A job the stack no longer knows is ended rather than failed.

The lines arrive once, whole, with the finished record. While the walk runs,
the screen shows only the stage it is at.

A `step` names no job, so every step heard while a walk started here runs is
taken as that walk's; a second walk started elsewhere at the same time would be
heard as this one. No other job carries a stage: the `job` envelope is an action
and a name. `WhatTheContractDoesNotCarryTest` holds both.

## Asked for, and not drawn yet

`D3-R10` is drawn as the handover's sentences. Adding more content and inviting
the household lead nowhere yet, because this app has no screen for either.
