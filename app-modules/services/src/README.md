# Services

What the app decides about what a stack runs that the stack has not already
decided for it. Two decisions, about a screen:

| | |
|---|---|
| `WithoutWhatWasLeftOut` | the services less those the forms asked for and the stack left out, which the stack also lists as absent and which a screen draws once, as left out, with what it would need |
| `WhetherItIsInstalled` | whether a service is one the stack has, in any state or because a running form brought it in, or one it could run and nothing asked for |

And one decision about what the phone keeps of it between launches:

| | |
|---|---|
| `KeepingWhatItRuns` | The newest listing of what each stack runs, sealed before it is kept, handed back on opening as a `WhatWasKeptOfWhatItRuns` with when it was read and now, and let go of where it does not read |

Only the listing is kept: never an action, its confirmation, or what a start
waits on. Listings older than the operator chose are let go of from the store
through `ForgetsOldReadings`, which the store implements.

It depends on `kernel`, and on Laravel's database in its store alone. A kept
listing is sealed through `Sealed` and stored through `ListingsKept`, a port
this module declares in `Internal` and answers in `Internal/Store`:

| | |
|---|---|
| `ListingsInTheDatabase` | `ListingsKept`, over the app's own database: one table, `services_readings`, created by this module's migration in `database/migrations` |

A row holds a stack's keyed hash, the shape the listing was written in, when it
was read, and the listing as this module sealed it. Nothing in this module but
the store names the database, and nothing but the composition root names the
store.

## What is deliberately not here

**How a stack is running, and how each service runs.** Both are the stack's
answer and arrive decided; this module holds no opinion about either.

**The forms a stack declares.** They are a second reading, asked of the stack
on the frame after the listing and kept nowhere.

**Telling a stack to start, stop or restart.** `Supervising` is the port and
`Supervisors` is the adapter; this module may not name the SDK (`N1-R16`) and
has nothing to say about a wire.
