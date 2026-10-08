# Framework, errors and boundaries

Part of [the rules](../../ARCHITECTURE.md#the-rules) `ARCHITECTURE.md` indexes.

## Framework coupling

| | Rule | Enforced by |
|---|---|---|
| A1 | No Eloquent, no Active Record. Persistence is a port the owning capability declares, and `Illuminate\Database` is named only in a store, a capability's `src/Internal/Store` and its own `database/migrations`, and in the `store-kit` those stores share | arch: no Eloquent and no query builder named anywhere (`ArchitectureTest`), and no `Illuminate\Database` in any file of any module outside a capability's store and migrations and a `store-kit`, or in the composition root (`OnlyAStoreReachesTheDatabaseTest`) |
| A2 | No facades. Dependencies arrive through constructors | phpstan `disallowed-calls` |
| A3 | No service location — `app()`, `resolve()`, `Container` | phpstan `disallowed-calls` |
| A4 | No container-reaching helpers — `config()`, `cache()`, `view()`, `__()` and the rest — outside the composition root, `config/` and tests | phpstan `disallowed-calls` |
| A5 | `env()` only inside `config/` | arch |
| A6 | No mutable static state — a static property, and a `static` inside a method body | arch: reflection over every module class for the property, a token read over every source for the variable |
| A7 | `Illuminate\*` forbidden in `kernel`, in a `capability` everywhere but its store: `src/Internal/Store` and `database/migrations`, and in a `store-kit` but for `Illuminate\Database` | arch: module kind (`ModuleBoundariesTest`), with a capability's store read apart by directory and a store kit's names under `Illuminate\Database` passed over. The dependency analyser reads a package per manifest and cannot scope one by path, so a capability that keeps something requires `illuminate/database` in its manifest and this rule is the wall |
| A8 | `Native\Mobile\Facades\*` only in `device` and `vault` | phpstan `disallowed-calls` |
| A9 | A service provider binds and does not work: no read, no request, no resolve in `register()`/`boot()`, or in a method of the provider either calls as it runs | phpstan: own rule |
| A10 | A table belongs to one owner: it carries its owner's prefix, is created only in that capability's own `database/migrations`, and is named in no other module's code | arch: `OnlyAStoreReachesTheDatabaseTest`, over every module's sources and migrations and the composition root |
| A11 | A store is reached through its port: nothing outside a capability's `src/Internal/Store`, in that module or any other, names a class in it, and only the composition root binds it | arch: `AStoreIsReachedThroughItsPortTest`, over every module's sources and the rest of `bootstrap/Composition`, with names resolved as PHP resolves them |
| A12 | A store takes and gives only what is sealed: every public method of a store class takes a `SealedPayload`, a `SealedStack` or the `Shape` and `Instant` beside them, and answers with a value built from those or a count | arch: `AStoreSeesNothingItCouldReadTest`, by reflection over every store class; test: `WhatThePhoneKeepsIsUnreadableOnDiskTest`, which keeps a summary and reads the raw database file for it |
| A13 | What the phone keeps is put in the clear to be sealed only by its owner's writer, and no writer is handed an offer, an agreement, a command's idempotency key, a credential, a session or pairing material | arch: `WhatThePhoneMayKeepTest`, every call to `Unsealed::of` in a source tree resolved as PHP resolves it and read against its register of `*AsKept` writers and the seal, which hands back what it opened; and every type a writer is handed walked by reflection to the types it holds, docblocks included |
| A14 | A store kit is reached from a store: a `store-kit` module is named only in a capability's `src/Internal/Store`, and never elsewhere in a capability, in a migration, in another kind of module or in the composition root | arch: `OnlyAStoreReachesTheDatabaseTest`, over every module's sources and migrations and `bootstrap/Composition`, with names resolved as PHP resolves them |

**Why A1 is first.** An Eloquent model cannot be constructed without a database,
so every test that touches one is an integration test wearing a unit test's
clothes. It is also the single largest source of hidden IO in a Laravel codebase:
a property access can issue a query. Neither is acceptable in a module that is
supposed to be pure.

**Why a store lives inside its owner, walled.** What the phone keeps is decided
and stored by the capability it belongs to: `health` decides what it keeps of a
stack's health, and `health`'s store keeps it. Modules will keep more and more
on the phone, and a module per kind of kept data would grow the application by
a module, a manifest and a kernel port for every class and migration. So the
store is a directory rather than a module, and four walls give it what a
module of its own would. A1 and A7 let the framework into that directory and its
migrations and nowhere else in the capability, so the rest of it still cannot
be run wrong. A11 keeps the store behind the port the capability declares, so
every decision is still tested over a fake. A12 keeps it sealed in and sealed
out, so a store can be handed nothing it could read. A10 names its tables for
their owner, `health_readings`, so a row on disk says whose it is, and no other
module names one: an owner that needs another's data asks that owner's
capability, never its rows. A store is recognised by where it is rather than by
a list here, so one written tomorrow is walled the moment its first class
exists.

**Why A13 is a wall beside them.** The four walls keep a store from reading
what it is handed, and none of them says what that may be. Everything kept is
an `Unsealed` before it is sealed, so the classes that make one are the whole
of the way into the phone's storage, and they are few enough to name: each
owner's `*AsKept`, which writes what it keeps in a shape it reads back. A
writer handed a session, an offer or the key of a command the stack may not
have received is one line from writing it down, and the phone would then keep
a credential beside the readings or send an agreement again on the next
launch. So a writer's inputs are walked to every type they hold, and none of
those may be one.

**Why the stores share a kit, and the kit is walled too.** Every store of
readings answers the same five questions over the same four columns, and a
store inside its owner has nowhere else to share them: the kernel may not name
the framework, and one capability may not name another. So the queries are
written once, in a `store-kit` module that owns no table and no migration. Each
store hands in the table that is its own, so A10 still names every table for
its owner, and A12 still judges the store, whose every method the kit answers.
A14 walls the kit from the other side: only a store names it, so the rest of a
capability still reaches what it keeps through its port alone.

**Why A7 costs something and is worth it.** Giving up `Collection` in domain code
is a real loss of convenience. What it buys is a domain that does not move when
the framework does, and typed collections that say what they hold
(`Findings`, not `Collection<int, mixed>`).

## The untestable primitives

| | Rule | Enforced by |
|---|---|---|
| B1 | Time only through the `Clock` port | phpstan `disallowed-calls`: `now`, `time`, `date`, `Carbon::now`, `new DateTime` |
| B2 | Randomness only through the `Entropy` port | phpstan `disallowed-calls`: `random_int`, `rand`, `uniqid`, `Str::random` |
| B3 | Filesystem only in adapters | phpstan `disallowed-calls`, scoped by path |
| B4 | No `sleep()`/`usleep()` — waiting is a port | phpstan `disallowed-calls` |

These four share one justification. Each is a hidden input: a function whose
result changes without its arguments changing. A test cannot pin it, so the code
around it either goes untested or the test becomes slow and flaky. Making them
ports turns "the session expired", "the backup is three days old" and "retry
after thirty seconds" into things a test simply states.

```php
// refused
$expires = now()->addHour();

// required
public function __construct(private Clock $clock) {}
$expires = $this->clock->now()->plus(Duration::hours(1));

// and in a test, no freezing of global state
$clock = new FrozenClock(Instant::parse('2026-09-11T12:00:00Z'));
```

## Errors and control flow

| | Rule | Enforced by |
|---|---|---|
| C1 | `Outcome` crosses module boundaries; exceptions do not | arch: no public `Api` method returns `void` |
| C2 | No `null` for absence — an explicit type | arch: no nullable return types on `Api` |
| C3 | Every thrown exception is module-owned, never bare `\Exception`/`\RuntimeException` | phpstan `disallowed-calls` |
| C4 | No `@` suppression | phpstan: ergebnis `NoErrorSuppressionRule` |
| C5 | `match`, never `switch`; and no `else` | phpstan: ergebnis `NoSwitchRule` + spaze `disallowedControlStructures` |
| C6 | No empty catch, and no `Throwable`/`Exception` caught without rethrowing | phpstan: own rule |
| C7 | No `empty()` | phpstan: own rule |
| C8 | No `?->` in `kernel` or a capability | phpstan: own rule, scoped by path |
| C9 | No nested ternary, and no `??` on an array subscript | phpstan: own rule |
| C10 | No argument that cannot change the answer | arch: no `preserve_keys` on `iterator_to_array` in a source tree |

**Why C10 exists at all.** `iterator_to_array($findings, preserve_keys: false)`
is correct, and over a collection of ours it is also unobservable: every one of
them holds a list, so both values of the argument produce the same array. It is
written to satisfy PHPStan, which wants a `list` and gets `array<int, T>` from
the default — so it is a line that exists for one checker and is invisible to
every other. Mutation testing is what finds it, and did: `FalseToTrue` survived
at two sites on the same afternoon, neither of them a bug and both of them a
line that could have become one. `WorstFirst::over()` collects by hand and says
why; this is that decision stopping being a convention.

**Why C7 is absolute.** `empty()` is true for `null`, `false`, `0`, `'0'`, `''`
and `[]`, and this application turns on exactly the distinctions it erases. A
stack with zero findings is healthy. A stack that has never been read is
unknown. A backup count of zero is a warning and a backup count that has not
arrived yet is a spinner. One function renders all of those as the same screen.

**Why C5 bans `else` as well as `switch`.** `switch` compares loosely, falls
through, and cannot be checked for the arm nobody wrote — which is the hole D4's
enums exist to close, so leaving `switch` available would reopen it. `else` is
subtler: it is where two branches begin drifting apart, and where a refusal gets
handled inline instead of being returned as an `Outcome` the caller has to open.
Returning early from the refusal leaves the happy path at one indent, reading top
to bottom.

**Why C1 is worth its cost.** This application spends its life talking to a
machine that may be off, asleep, on another network, or mid-update. Unreachable
is not exceptional here — it is a normal Tuesday. Modelling it as a thrown
exception makes the common case the one the compiler cannot see you forgot.

```php
public function repair(FindingId $id): Outcome
{
    return $this->stack->repair($id);
}

// the caller cannot quietly ignore a refusal
$outcome->either(
    done: fn (Repaired $r) => $this->show($r),
    refused: fn (Refusal $r) => $this->explain($r),
);
```

## Types and data shape

| | Rule | Enforced by |
|---|---|---|
| D1 | No `array` in a public `Api` signature — value objects or typed collections | arch: reflection over every published method |
| D2 | No primitive obsession: ids, tokens, durations are types | arch: no `string`/`int`/`float` parameter outside a named constructor |
| D3 | No `mixed` in public signatures | phpstan (level max + type coverage 100%) for a missing type, plus arch for `mixed` itself — coverage counts a type as declared, and `mixed` is one |
| D4 | Enums for every closed set, never string constants and never a literal compared against | arch (names) + test over source tokens (literals) + shipmonk `ForbidMatchDefaultArmForEnums` |
| D5 | No bare `true`/`false` at a call site — name the argument or split the method | phpstan: own rule |
| D6 | No unnamed numeric literal in a method body | phpstan: own rule + SonarCloud |
| D8 | A closed set of strings is an enum: no class compares a value against three or more of its own string constants, and no lookup asks a list of three or more strings, but where an outside vocabulary is read in its own words | phpstan: own rule, over what ships; arch: every file it leaves alone is there, and the list only shrinks |
| D9 | One value has one home: a value two classes declare as constants is declared once and referred to, unless the two only mean different things | phpstan: own rule with a collector, over what ships and `tests/Support`; arch: every constant it leaves alone is still declared, and the list only shrinks |

D2's payoff is concrete: a stack id and a service id are both strings, and
nothing stops you passing one where the other belongs. `StackId` and `ServiceId`
are two types, and the mistake stops compiling.

D8 and D9 are numbered as `php-mutation-gate` numbers the same two rules, and
they close what D4 and D6 leave open. D6's cure is a name, and D4's cure for a
sentinel is a `private const`; neither says where the name lives. A heartbeat
named in the class that listens and named again in the class that decides a
stream went quiet passes both, and is one decision kept in two places. So a
value has one home, a length of time is counted from `SecondsIn` and a step of
a thousand from `DecimalPrefix`, and a default somebody could tune is a value
with a named `standard()`. Where two constants hold the same value and mean
different things, one is listed with what it means; where a stand-in or a
double spells what the side it stands in for spells, the two are kept apart on
purpose and listed too, because one home would have the reader agree with what
it reads by construction.

## Boundaries

| | Rule | Enforced by |
|---|---|---|
| E1 | Module kind enforcement | arch, generated from each manifest |
| E2 | `Api` is the published surface; `Internal` is unreachable | arch |
| E3 | The SDK is named only by the `sdk` adapter and by the stand-in for it | composer + arch |
| E4 | `Native\*` confined to `design`, `surface`, `wayfinding`, `device`, `vault` | arch: module kind |
| E5 | A listener obeys the module kinds, checked in the dispatcher rather than in the imports | test: the booted composition root |

## The module API

A module publishes commands and queries, and nothing dispatches them. There is
no bus, no handler and no message object separate from the thing that handles
it: the class **is** the message, `__invoke` is the dispatch, and the stack
trace of a failure runs from the screen to the SDK without passing through a
`switch` on a class name.

| | Rule | Enforced by |
|---|---|---|
| M1 | A query never answers with `Outcome`; a command answers with nothing else | arch |
| M2 | A class under `Api\Commands` or `Api\Queries` has exactly one public method | arch |
| M3 | Every `Api\Commands\*` constructor takes an `IdempotencyKey` | arch |

**Why M3 is not optional.** A phone loses wifi mid-request and cannot tell
whether the stack applied the update or never heard the question. Without a key
the only safe answer is to do nothing and ask the operator, which is the worst
screen in the application. With one, retrying is free — so the retry can be
automatic and the operator never sees the question.
