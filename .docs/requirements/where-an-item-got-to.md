# Where an item got to

The answer to *where is my show?*: one item followed through the services, how
sure the trace is of it, how far it got and why it stopped, what has been tried,
where the services disagree, and for a series what is here season by season.
The code is the trace half of `N8` in `app-modules/kernel` and
`app-modules/sdk`, drawn by `WhereThisGotTo` and reached from every stalled
item on `WhatStoppedComingIn`.

Each row says what the requirement asks and what in this repository answers it.
The spec is canonical; where this page and a requirement disagree, the
requirement is right and this page is a defect.

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N8-R4` | A trace carries its confidence, and an uncertain one is not drawn as certain | `HowSureTheTraceIs` is required on every followed trace, and the screen draws it first, an uncertain one in emphasis saying it may not be the item meant |
| `N8-R5` | Where the contract carries what is outstanding episode by episode, the gaps are shown rather than a completion figure alone | `HowMuchOfItIsHere` counts a series by `HowMuchOfASeasonIsHere`, each holding its outstanding episodes with the stage each rests at. The screen draws every season and every outstanding episode |
| `N8-R6` | A stage the contract names is drawn as that stage, and *not monitored* is never drawn as nothing found | Every stage is the stack's word, drawn as it came with the plain sentence beside it, on the furthest stage, the way it came and each outstanding episode |
| `N8-R8` | The operator's trace is not reused as a member's view of their own request | `WhereThisGotTo` is an operator screen and carries the pipeline's internals; nothing in the household module reads a trace |
| `N8-R9` | A trace that could not be read is told apart from there being none | A trace that could not be read is an obstacle, drawn as one. Nothing matching is `WhereItGotTo::nothingAskedFor()`, its own answer with its own sentence |

A title can hold a space or a slash, so the route carries it encoded and the
router hands it back as it was drawn.

## Asked for, and not drawn yet

`N8-R1`, `N8-R2`, `N8-R3` and `N8-R7` are about quality choices, dispositions
and watches, each a reading of its own.
