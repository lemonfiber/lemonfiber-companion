# Requests

What the app decides about what a stack's household asked for that the stack
has not already decided for it. One decision, about what the phone keeps of it
between launches:

| | |
|---|---|
| `KeepingWhatWasAsked` | The newest reading of what each stack's household asked for, sealed before it is kept, handed back on opening as a `WhatWasKeptOfWhatWasAsked` with when it was read and now, and let go of where it does not read |

Only the reading is kept: each request as the stack reported it, with the
reason the stack gave where one was turned down. Never an approval, a decline,
the reason an operator typed for one, or what the stack answered when it was
told. Readings older than the operator chose are let go of from the store
through `ForgetsOldReadings`, which the store implements.

It depends on `kernel`, on `store-kit` from its store, and on Laravel's
database in its store alone. A kept reading is sealed through `Sealed` and
stored through `RequestsKept`, a port this module declares in `Internal` and
answers in `Internal/Store`:

| | |
|---|---|
| `RequestsInTheDatabase` | `RequestsKept`, over the app's own database: one table, `requests_readings`, created by this module's migration in `database/migrations`, and queried through `store-kit`'s `ATableOfReadings` |

A row holds a stack's keyed hash, the shape the reading was written in, when it
was read, and the reading as this module sealed it. Nothing in this module but
the store names the database, and nothing but the composition root names the
store.

## What is deliberately not here

**Where a request stands, how big it is, and whether it waits on a decision.**
Each is the stack's answer and arrives decided, in `Wanted`, `Size` and
`Waiting`; this module holds no opinion about any of them.

**Approving or turning one down.** `Wanting` is the port and `Requests` is the
adapter; this module may not name the SDK (`N1-R16`) and has nothing to say
about a wire.
