# Backups

Empty, and the reason is worth more than the directory is.

**Nothing in `N1`–`N4` asks this app to do anything with a snapshot.** The
companion's 105 requirements cover pairing, the verdict, the services, logs,
requests, updates, the household surface and the platform. Backups are not among
them. So this is not a module somebody started and left — it is a name held for
a surface nobody has specified.

## The contract does carry them

`lemonfiber` has a `backup` envelope, and the `update` payload carries a
`backup` field naming the snapshot taken before a run. So the wire is ahead of
the app here, which is the opposite of the usual direction and the reason this
file exists rather than a bare directory: an empty module reads as work nobody
got to, and this one is waiting on a decision rather than on effort.

## What would fill it

The same shape the other capability modules have: the decisions this app makes
about a snapshot that the stack has not already made. Which of several to offer
first, what a restore would cost the household, whether one is old enough to be
worth saying so about. Not *taking* a snapshot and not *restoring* one — those
are the stack's, reached through a port and an adapter the way every other verb
is.

`HowToUndoIt::Restore` already names a restore as one of the two ways back from
an update (`N2-R19`), and it is the stack that performs it. That is as far as
this app goes today.

## Before writing anything here

Raise the requirement first (`N1-R17`). A surface built because the wire happens
to carry a field is a surface nobody asked for, and `N2-R14`'s whole argument is
that a gap between the contract and a requirement gets answered where the
requirement lives rather than filled in by whichever side noticed.
