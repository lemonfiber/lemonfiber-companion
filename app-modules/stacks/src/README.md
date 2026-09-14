# Stacks

Empty, and unlike its neighbours this one is empty because there is nothing for
it to decide.

A capability module holds the decisions this app makes that the stack has not
already made for it — `health` orders findings worst-first, `updates` puts what
did not arrive above what did. Both exist because the server sends an order that
answers a different question from the one a person reads in.

**What this app knows about stacks and their services, the stack already
decided.** Which services there are, what form each belongs to, what state each
is in, what leans on what, and how long a verb takes it away for all arrive
decided. `N2-R7` offers start, stop and restart; `N2-R8` states what one
disturbs. Neither is a decision — they are a port, an adapter and a screen, and
they live where those live.

## Where the stack-facing code actually is

| | |
|---|---|
| `Modules\Kernel\Api` | `Stack`, `StackId`, `ServiceId`, `Daemons`, `Supervising`, `Disturbances` |
| `Modules\Sdk\Api` | `Rosters`, `Supervisors` — the readers and the adapter |
| `Modules\Operator` | `WhatThisStackRuns` and its folds |

That is not a mistake to be tidied up into here. A value belongs in `kernel`,
which every module may use; moving it would make one capability module a
dependency of another, which the kinds forbid.

## What would fill it

An ordering or a narrowing over services that the stack does not send and a
screen should not invent — the shape `WorstFirst` has. `WhatLeansOnIt` is the
nearest candidate if a screen ever needs the dependency graph read in an order
the wire does not give it.

Until then, a module with nothing in it is the honest answer, and this file is
here so that reads as a decision rather than as an oversight.
