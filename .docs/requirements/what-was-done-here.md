# What was done here, and with what

What a machine has changed about itself, how far each change goes back and how
far back the record goes at all — and where every service it runs comes from.
The code is the `N11` half of `app-modules/kernel` and `app-modules/sdk`, drawn
by `WhatWasChangedHere` and `WhereThisComesFrom`.

Each row says what the requirement asks and what in this repository answers it.
The spec is canonical; where this page and a requirement disagree, the
requirement is right and this page is a defect.

## The record, and where it ends

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N11-R1` | The horizon is stated, and the end of what is kept is never presentable as nothing having happened | `TheRecord`, which cannot be constructed without the stack's own sentence about how far it reaches — `TheRecordHasNoHorizon` refuses a blank one, and `Records` refuses a report that leaves it out rather than defaulting it. The screen says it where the list ends, which is where somebody scrolling arrives, and under an empty record too. Whether anything older was dropped is the stack's sentence to say — it says *nothing has been dropped* or *anything older has been dropped* — so the lead-in claims neither, and a change missing from the record is never drawn as one that did not happen |
| `N11-R9` | An empty record is told apart from one that could not be read | `WhatWasRecorded`, whose two arms are a record and an obstacle, never an empty record standing for either. An empty `TheRecord` is an answer with a horizon; a stack that could not be asked reaches the screen's other branch and draws no record at all |
| `N11-R5` | A change record is not rendered as a log | `WhatWasChangedHere` draws an entry per change, headed by what it did, under the moment it was made — never a line per change in the order it was written |

## What each change says

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N11-R2` | A change carries how it would be reversed, and one that cannot be reversed says so rather than omitting the field | `HowFarItGoesBack`, with *none* as a case of its own rather than an absent value; `Change::made()` takes it as a required argument. Where putting it back stops short, `WhereItStopsShort` carries why, and what to do instead where the stack suggests something — `Records` refuses a suggestion that arrives without its reason |
| `N11-R3` | A change shows how many changes accompanied it | `Change`, which refuses fewer than one — a change that came alone says one, the least it can say. Drawn on every row, *on its own* included, because undoing one line of an operation that made more leaves a machine in a state nobody chose |
| `N11-R10` | Changes at the same instant are ordered deterministically and are not presented as one preceding the other | `HowTheRecordReads` draws consecutive changes made at one moment under a single *when*, in the stack's order, rather than each with a time where the one above would read as the later. `WhenItWasMade` carries a clock the stack could not read as an arm of its own, never as 1970, and an unreadable moment is never the same moment as another — nobody knows those changes happened together, so they are not drawn together |

## Where each service comes from

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N11-R6` | A service's provenance shows its image, the digest it is pinned to, its upstream and its licence | `WhereItComesFrom`, which cannot be built with any of its words blank — `OriginSaysNothing` names the one that was, and `Origins` refuses the row first so it reaches the screen as an obstacle rather than a raise. The image and the version are kept apart as the stack keeps them and drawn together, because a version without its image names nothing that can be fetched. **The digest is not drawn**: see below |
| `N11-R7` | A licence is shown for every service, not only where it is unusual | The licence is a required word of `WhereItComesFrom`, and `WhereThisComesFrom` draws it on every row, whatever it says |
| `N11-R8` | Where an upstream cannot be reached, the pin and the licence are still shown | Nothing here reaches an upstream. `Provenance` asks the stack and nothing else, and every word on the screen is one the stack declared — which the screen says once, over the list, so a licence is not read as a check made today |

## Asked for, and absent

`N11-R4` asks that a change's reason be shown *where it carries one*. None
does: the stack's journal records what a change did, to what and how to undo it,
and nothing about why it was made. `WhereItStopsShort` is not that reason — it
is why putting a change back stops short — and the screen says so in its own
words rather than letting one stand in for the other.

`N11-R6` asks for the digest a service is pinned to, and the screen draws its
version instead. The stack does not carry a digest: its manifest pins each image
by tag, and the `provenance` envelope's `pinned` is that tag. `E1-R1` requires
every image pinned to an immutable digest with the tag beside it, so the gap is
the stack's to close first; when the envelope carries a digest, it is drawn
beside the version rather than in place of it.
