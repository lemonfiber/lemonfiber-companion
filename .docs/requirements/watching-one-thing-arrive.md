# Watching one thing arrive

The first acquisition, narrated end to end: a walkthrough started from the
phone, followed while it runs, and its record drawn once it finishes, with where
it stopped and why, what the import did, and what to do next. The code is the
walkthrough half of `N15` in `app-modules/kernel` and `app-modules/sdk`, drawn by
`WatchingOneArrive` and reached from the home screen of a stack.

Each row says what the requirement asks and what in this repository answers it.
The spec is canonical; where this page and a requirement disagree, the
requirement is right and this page is a defect.

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N15-R6` | A walkthrough's lines are carried as given, never reconstructed or reordered | `Walkthroughs` reads every line in the order the stack sent it into `TheLinesItSaid`, and `ALineItSaid` holds the sentence and its detail exactly as said. `HowAWalkthroughReads` hands them on in that order, and the screen draws them whole under one heading, as a record: a walk that finished while nobody was looking reads the same as one that was watched |
| `N15-R7` | Where a walkthrough names what to do next, that is shown | The handover is `WhatComesNext`, read in the stack's order, and the screen draws a sentence for each thing it names; where to watch leads to `WhichAppToWatchOn`. A walk that names nothing to do next draws no handover |
| `N15-R8` | *Already here* is its own outcome, never a search that found nothing | `AWalkthrough::wasAlreadyHere()` carries the stack's own field, and the screen says it first, in a sentence of its own. Why a walk stopped is `WhyTheWalkthroughStopped`, a separate reading, and a search that matched nothing is one of its ten causes |
| `N15-R9` | The app defines no word the glossary does not carry | Before a walk the screen asks the stack for its glossary and draws the road a walk takes, each `WalkthroughStep` in the contract's order. A step is drawn as the glossary's word for it where the glossary carries it, explained in place by `ShowsWhatItsWordsMean`, and as it came where it does not. `WalkthroughStep` keys no sentence of its own |
| `N1-R27` | A screen whose content changes while open refreshes on a stated cadence | While the walk runs, `WatchingOneArrive::whileItRuns()` asks after the handle at `HowOften::WhileWorkRuns`, and the screen says how often. A finished record is never asked after again |

A glossary that cannot be had before a walk is what stood in the way, drawn as
such.

Starting one names the title typed, or nothing, which the stack reads as
*suggest something likely to work*; the suggestions it answers with can each be
started with a tap. A job the stack no longer knows is ended rather than failed.

The stack also narrates each step on its event stream as it happens. This app
does not read that stream: the lines arrive once, with the finished record.

## Asked for, and not drawn yet

`D3-R10` is drawn as the handover's sentences. Adding more content and inviting
the household lead nowhere yet, because this app has no screen for either.
