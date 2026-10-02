# News

What is new on each stack, against the newest of each kind the operator has
seen there. There are three kinds, `KindOfNews`: an update, a request and a
problem. Each is newer by an order the stack vouches for. An update is newer by
its place in the stack's record, a request by its number, and a problem by when
it began.

| | |
|---|---|
| `Noticing` | What is new among the items of a kind on a stack, and the operator having seen one item or all of them. It is the keeper `ForgetsAStack` asks when a stack is removed |
| `MarkingAsNew` | Which kinds a stack marks as new: all three until the operator switches one off. Switching a kind off forgets the newest of it seen |

And the values they take and give:

| | |
|---|---|
| `KindOfNews` | The three kinds, each by the value a kept row names it by |
| `AnItem` | One update, request or problem, by what names and orders it among its kind |
| `TheItems` | Every item of one kind a stack holds, newest first, and what is newer than one of them |
| `WhatIsNew` | The items of one kind on one stack that are new, newest first |
| `NotAnItem` | Why something could not be made an item |

**The first sight of a kind records what is current as seen.** A stack read for
the first time, or a kind switched on again, marks nothing, and only what
arrives after it is new. An update the stack no longer lists leaves only its
newest release new.

It depends on `kernel`, and on Laravel's database in its store alone. What it
keeps is sealed through `Sealed`, named by the stack's keyed hash, and stored
through `NewsKept`, a port this module declares in `Internal` and answers in
`Internal/Store`:

| | |
|---|---|
| `NewsInTheDatabase` | `NewsKept`, over the app's own database: one table, `news_kept`, created by this module's migration in `database/migrations` |

A row holds a stack's keyed hash, the shape it was written in, when it was
noted, and everything kept about the stack as this module sealed it: the kinds
switched off and the newest item seen of each kind, `WhatIsKeptOfNews`. A
marker holds only what names and orders an item, never what the stack says
about it. `TheNewsAsKept` writes and reads it, `NewsOfAStack` seals, keeps,
opens and forgets it for both services, and `TheNewestItem` takes the newest of
a list. `KeptNews` and `WhetherItWasKept` carry an answer out of a fold.
Nothing in this module but the store names the database, and nothing but the
composition root names the store.
