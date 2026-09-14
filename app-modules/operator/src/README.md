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

## `N2-R4`, `N2-R5`, `N2-R7` — every action arrives as a job

> Where the core offers a repair, the app MUST offer it, and MUST state what it
> does, what else it affects, and whether it can be undone, before asking for
> confirmation.

**The offer half is built.** `Mending` asks what a stack would put right,
`WhatWouldBePutRight` states all three of `N2-R4`'s clauses before anything asks
for a yes, and `Offers` reads both the handle and the listing. What follows
recorded why the port could not be written before the job reading existed; it is
kept because the reasoning is what shaped the port, and because the *agreeing*
half is still unbuilt for one part of it.

`Confirmed` — a yes that rendering a finding cannot produce — and `Carried` are
still reached by nothing. The agreeing half needs what the offer half did not: a
vocabulary for what became of each repair (`fixed`, `fix_failed`, `stopped`,
`declined`, `would_overwrite` on the `repair` envelope's `mended[]`), and a
screen holding the offer as a **live** `Reading`, since `Confirmed::against()`
refuses a retained one by `N1-R39`. That is a second capability rather than the
rest of this one, and it is the next thing here.

### The offer and the yes are one request, read twice

`POST /api/actions/repair` takes `confirm`, `offer` and `agreed`. Unconfirmed it
says what each repair would do and changes nothing; confirmed, and naming the
listing it answers, it carries out what was agreed to. `N2-R5` is the
requirement that those stay two things, and the engine's `Consent::Given`
carries the listing's name so `N2-R6` can be settled where the machine can be
seen.

The SDK names that endpoint as of `Lemonfiber\Sdk\Repair`, and the two refused
consent arrangements are unrepresentable there rather than refused at runtime.

### What was missing was the answer, not the asking

`answering()` in `lemonfiber-api` has no arm for `Command::Repair`, so it falls
to `Answering::Later`: the action answers **202 with the `job` envelope**, for
the offer half as well as the acting half. The `repair` envelope — the one
carrying `offered`, which is what `N2-R4` needs an operator to read — arrives
through `GET /api/jobs/{job}`. The SDK names that now (`whatBecameOf()` and
`letGoOf()`), which is what unblocked the port.

That is not a detail of plumbing. It changes the shape of the port: asking what
a stack would repair is not a question with an answer, it is a question with a
handle, and the answer is three states across two statuses — still running,
finished with the envelope, and *ended* rather than finished. A port that
collapsed the third into either of the others would either spin forever or
report a working machine as unreachable.

It also runs into `N1-R41`, which says the app must not retain an undelivered
action, must not replay one on reconnecting, and must not present an action as
pending. A job handle is very close to a pending action, and the distinction is
real rather than semantic: a job the stack acknowledged *did* happen, and asking
after it is a read. Replaying an action it never received would be inventing one.

So the port took the shape the job reading forced rather than one that would
have had to change: two methods, because asking is not answering, and a reading
with four arms because *ended* is neither running nor finished. `N2-R7`'s start,
stop and restart are in exactly the same position and will take the same shape —
every action on this surface is a job, and `Job`, `Underway` and
`HowTheOfferIsGoing` are already the types for one.

### One asymmetry worth knowing

`Restore { consent: RestoreConsent::List }` *is* `Answering::Now`. The listing
form of restore answers immediately and the listing form of repair does not,
though neither changes anything — the core refuses `disruptive` to the offer
half by name, on the ground that a run disturbing something to say what it
*would* do has already done it. Whether that asymmetry is deliberate is the
core's to say; it is recorded here because it is the one thing that would let
`N2-R4` be answered without a job at all.
