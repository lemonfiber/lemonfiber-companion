# Store kit

What every capability's store of readings does over its own table, written
once. A module of its own kind, `store-kit`: it owns no table and no migration,
names no table, and keeps nothing itself.

| | |
|---|---|
| `ATableOfReadings` | The newest sealed reading of each stack, in the table a store hands in: keep one over the last, find the newest, forget a stack's, forget those read before a moment, forget them all. A row this build cannot read is said to be one, and a database that will not answer keeps and finds nothing |
| `AnswersFromItsTable` | The store side: a store using it answers its owner's port from `ATableOfReadings` and says only which table is its own |

Each owner's table is created by that owner's migration, with four columns: the
stack's keyed hash, the shape the reading was written in, when it was read, and
the payload as the owner sealed it. Everything this module is handed is sealed,
so it holds nothing it could read.

It depends on `kernel` and on Laravel's database, and is named by a
capability's `src/Internal/Store` and by nothing else (A14).
