# Stacks

Nearly empty, and what is in it is here for a reason that is not the usual one.

A capability module holds the decisions this app makes that the stack has not
already made for it — `health` orders findings worst-first, `updates` puts what
did not arrive above what did. Both exist because the server sends an order that
answers a different question from the one a person reads in. **This module holds
no such decision and is not expected to.**

## What is here, and why it could not live anywhere else

`AStacksScreen` is where every path under one machine is written, and it used to
sit in `Modules\Operator\Internal` — which was right while the operator was the
only surface that drew a machine. It is not any more. `Modules\Household` draws
a member's own reading of a stack, and each surface has to be able to send
somebody to the other: the operator's hub offers the way into the member's
reading, and that reading ends with the way back to the machine.

A surface may depend on a kernel, a design and a capability module and never on
another surface. So a path both of them need can live in neither, and spelling
it in both is the one drift `AStacksScreen` exists to prevent — a pattern
registered under one spelling and navigated to under another, which is a button
that does nothing on a handset with no error anywhere.

It is a *fact* about a stack rather than a decision about one, which is why it
does not make this module into the thing the paragraph above says it is not.

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
| `Modules\Household` | `WhatYouAreOwed`, which is a member's reading of the same machine |

That is not a mistake to be tidied up into here. A value belongs in `kernel`,
which every module may use; moving it would make one capability module a
dependency of another, which the kinds forbid.

## What would fill it

An ordering or a narrowing over services that the stack does not send and a
screen should not invent — the shape `WorstFirst` has. `WhatLeansOnIt` is the
nearest candidate if a screen ever needs the dependency graph read in an order
the wire does not give it.

Until then, a module holding only what two surfaces share is the honest answer,
and this file is here so that reads as a decision rather than as an oversight.
