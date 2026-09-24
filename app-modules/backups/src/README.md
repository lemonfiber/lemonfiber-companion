# Backups

This module holds no code.

It is a capability module, so what belongs here is the app's own decisions
about a copy: the ones the stack has not already made. Taking a copy, restoring
one and listing what there is are the stack's, reached through a port in
`kernel` and an adapter in `sdk` the way every other verb is, and none of that
belongs here.

[`N6`](https://github.com/lemonfiber/spec/blob/main/10-functional/features/n-companion/n6-taking-a-copy.md)
is the requirement for taking a copy and putting it back. The part of it this
app has today is the difference between a rollback and a restore as the ways
back from an update: `Modules\Kernel\Api\HowToUndoIt` holds the two,
`Modules\Sdk\Internal\Endings` reads which one the stack named for each
service, and the operator's update screen says which, and that a restore brings
the data with it.

The `update` envelope's `backup` field — where the copy taken before an update
was written — is not read. The stack performs a restore; the app names which way
back the stack offered and does not describe a file on the machine it cannot
reach.
