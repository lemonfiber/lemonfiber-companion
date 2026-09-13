# The operator surface

One screen so far, and the requirements waiting here are mostly about what an
operator is allowed to be shown — which makes them easy to break by adding a
field rather than by making a decision.

## `N2-R12` — nothing here edits a credential's value

> The app MUST NOT offer to set or change a credential's value.

The feature says what this leaves room for: the app can report that a credential
is refused and offer the reconciliation lemonfiber already has; "it is not a
place to type a provider password into over a LAN".

The contract is built the same way. `CredentialHeld` carries a name, a location,
a state, its consumers and a fingerprint, and says of itself that there is
"deliberately no value here, and no field a value could be put in later without
the change being visible in review". A credential's plaintext reaches this side
in one field of one envelope, under `revealed`, and nothing here needs it.

### The half that is a gate

`tests/Feature/TypesThatMustNotMeetTest.php` refuses any class under
`Internal/Screens` that names `Credential` in a signature or a property. A
screen that can be handed one is a screen with somewhere to bind a field to, and
binding it is a small edit somebody makes while fixing something else — not a
decision anybody would defend in review, which is exactly why it needs a rule
rather than a reviewer.

Written before the screen exists, deliberately. A rule added after the surface
it governs is a rule written around whatever is already there.

### The half that is not

Whether the app *offers* a credential-writing action is not checked. The only
write path is `Client::act()`, which takes the endpoint as a string, so the
check would be on a literal argument — and today nothing calls `act()` at all,
because `tests/Feature/NothingReachesAStackUnpinnedTest.php` keeps every
transport type unnamed until pinning lands.

That is worth being precise about: **`N2-R12` is currently held by a rule that
exists for another reason.** When the pinning list opens, it stops being held,
and nothing will say so. The rule to write at that point is the one that reads
the endpoint argument, and it belongs with the other `act()` rules rather than
here.

## `N2-R8` — a duration the core never sends

> A disruptive action MUST state what it disturbs and for how long before it is
> confirmed.

The first half is answerable and the second is not. `kinds.lifecycle` — where
start, stop and restart live — carries `plan.services`, `plan.forms`,
`plan.profiles` and `plan.dropped`, each named individually, which is what lets
a screen list what an action disturbs rather than summarise it. Nothing in it
answers *for how long*, and nothing in `start`, `undo`, `beside` or `stuck`
does either. The only time estimates in the contract are `dashboard.eta`,
`household.estimate`, `bandwidth.seconds` and `step.eta`, and `step` is the
walkthrough — the machine-side setup flow this app is required not to carry out
(`N1-R35`).

An estimate written here would be a guess at something the stack knows and this
side does not: how long a service takes to come back depends on what it was
doing when it stopped, what depends on it, and what the machine is. It would be
wrong in exactly the cases an operator most needs it, and wrong silently.

Raised as [lemonfiber/spec#336](https://github.com/lemonfiber/spec/issues/336).
`N2-R4` is the same requirement shape for repairs and the contract *does* answer
it there, which is why `Repair` can hold that one by construction and there is
no equivalent type here.

## `N2-R2` — findings worst first

> Findings MUST be ordered by severity, worst first.

`Modules\Health\Api\Queries\WorstFirst` makes the decision and `HowThisStackIs`
asks for it. The order is applied on the screen rather than trusted to arrive:
the envelope carries findings in the order the checks ran, which looks ordered
and is not — and the failure is invisible on any report whose worst finding
happens to have run first.

### The gate

`F8`. A class under `Internal/Screens` that names findings at all names
`WorstFirst` too, checked over the text of the file. Text rather than a call
graph because the violation is a screen that names the query *nowhere*, and an
absence has no call site to follow to.

It is on the screen rather than on the query for the same reason it had to be
written the day a findings screen existed: a sorter nothing calls passes its own
test forever, so `WorstFirstTest` would have stayed green through every screen
that never asked.

### Narrowing composes with it, and the order is not reversible

`InCategory` narrows and deliberately does not reorder, so the screen narrows
and then sorts. The other way round would sort rows that are about to be thrown
away, and a query that both filtered and ordered would leave no way to say which
happened first.
