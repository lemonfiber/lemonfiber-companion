# Architecture

This document is the contract. Where it and the code disagree, one of them is a
bug — and the tests in `tests/Arch` exist so that it is usually the code.

Three layers enforce what follows, deliberately overlapping:

| Layer | Enforces | Fails at |
|---|---|---|
| `composer.json` per module | which modules may reach which | dependency resolution |
| PHPStan (`disallowed-calls`, `cognitive-complexity`, ergebnis, shipmonk) | what code may call and how complex it may get | static analysis |
| Pest (`tests/Arch`, `tests/Templates`) | shape, naming, boundaries, Blade | the test suite |

No layer is sufficient alone. Composer cannot see a namespace used without
being required in a monorepo that autoloads everything; arch tests cannot read a
Blade template; neither reads the lock file. The overlap is the point.

---

## The shape

```
app/                      the composition root — the ONLY place a port meets an adapter
app-modules/
  kernel/                 ports, values, outcomes.            depends on NOTHING
  design/                 EDGE components + theme tokens

  connection/             pairing, session, multi-stack       (N1)
  stacks/                 stacks and their services
  health/                 verdict, findings, repairs          (N2)
  backups/                snapshots
  updates/                versions, apply, undo

  operator/               navigation + screen composition     (N2)
  household/              navigation + screen composition     (N3)

  sdk/                    the only module that names the SDK  (N1-R16)
  device/                 permissions, notifications          (N4)
  vault/                  secure storage, app lock            (N4)
```

Every module declares its kind in its own manifest:

```json
"extra": { "lemonfiber": { "kind": "capability" } }
```

The kind is not decoration. `tests/Arch/ModuleBoundariesTest.php` reads it and
generates that module's rules, so **a module added without rules is not a module
without rules** — it inherits its kind's constraints the moment it exists. This
is the difference between a convention and an invariant: nobody has to remember.

### What each kind may depend on

| Kind | May use | May never use |
|---|---|---|
| `kernel` | nothing. Not Illuminate, not Native, not the SDK | everything |
| `capability` | `kernel` | Illuminate, Native, the SDK, other capabilities, adapters, surfaces |
| `design` | `kernel`, `Native\Mobile` | the SDK, capabilities, surfaces |
| `surface` | `kernel`, `design`, capabilities, `Native\Mobile` | the SDK, adapters, the other surface |
| `adapter` | `kernel`, the one package it adapts | capabilities, surfaces, other adapters |

Two consequences worth stating plainly:

**A capability module cannot be run wrong.** It has no framework, no network and
no clock of its own, so a test of it is a unit test whether or not anyone
intended one.

**`modules/sdk` is the only manifest that requires `lemonfiber/sdk-php`.** N1-R16
therefore stops being a rule a reviewer enforces and becomes a fact the
dependency resolver enforces: a surface module that types `Lemonfiber\Sdk` fails
`composer-dependency-analyser` because its own manifest does not require it.

### Published surface (E2)

A module exposes `Modules\<Name>\Api` and nothing else. Everything under
`Modules\<Name>\Internal` is unreachable from other modules, enforced by an arch
rule.

```
app-modules/health/src/
  Api/            ← other modules may name these
    Health.php            the port's consumer-facing entry
    Verdict.php
    Findings.php
  Internal/       ← nothing outside this module may name these
    FindingRanker.php
    VerdictPolicy.php
```

The benefit is refactoring: anything in `Internal` can be renamed, split or
deleted without reading another module, because nothing outside can be pointing
at it. That guarantee is worth more than the one directory it costs.

---

## The rules

Each rule below is enforced somewhere. Where a rule is not yet enforced
mechanically, it says so — an unenforced rule is a wish, and labelling it
honestly is better than pretending.

### Framework coupling

| | Rule | Enforced by |
|---|---|---|
| A1 | No Eloquent, no Active Record. Persistence is a port; adapters own the storage | arch: no `Illuminate\Database` outside adapters |
| A2 | No facades. Dependencies arrive through constructors | phpstan `disallowed-calls` |
| A3 | No service location — `app()`, `resolve()`, `Container` | phpstan `disallowed-calls` |
| A4 | No container-reaching helpers (`config()`, `cache()`, `auth()`, `request()`) outside adapters | phpstan `disallowed-calls` |
| A5 | `env()` only inside `config/` | arch |
| A6 | No mutable static state | arch: reflection over every module class |
| A7 | `Illuminate\*` forbidden in `kernel` and every `capability` | arch: module kind |
| A8 | `Native\Mobile\Facades\*` only in `device` and `vault` | phpstan `disallowed-calls` |
| A9 | A service provider binds and does not work: no read, no request, no resolve in `register()`/`boot()` | phpstan: own rule |

**Why A1 is first.** An Eloquent model cannot be constructed without a database,
so every test that touches one is an integration test wearing a unit test's
clothes. It is also the single largest source of hidden IO in a Laravel codebase:
a property access can issue a query. Neither is acceptable in a module that is
supposed to be pure.

**Why A7 costs something and is worth it.** Giving up `Collection` in domain code
is a real loss of convenience. What it buys is a domain that does not move when
the framework does, and typed collections that say what they hold
(`Findings`, not `Collection<int, mixed>`).

### The untestable primitives

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

### Errors and control flow

| | Rule | Enforced by |
|---|---|---|
| C1 | `Outcome` crosses module boundaries; exceptions do not | arch: no public `Api` method returns `void` |
| C2 | No `null` for absence — an explicit type | arch: no nullable return types on `Api` |
| C3 | Every thrown exception is module-owned, never bare `\Exception`/`\RuntimeException` | phpstan `disallowed-calls` |
| C4 | No `@` suppression | phpstan: ergebnis `NoErrorSuppressionRule` |
| C5 | `match`, never `switch`; and no `else` | phpstan: ergebnis `NoSwitchRule` + own rule |

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

### Types and data shape

| | Rule | Enforced by |
|---|---|---|
| D1 | No `array` in a public `Api` signature — value objects or typed collections | arch: reflection over every published method |
| D2 | No primitive obsession: ids, tokens, durations are types | arch: no `string`/`int`/`float` parameter outside a named constructor |
| D3 | No `mixed` in public signatures | phpstan (level max + type coverage 100%) |
| D4 | Enums for every closed set, never string constants | arch + shipmonk `ForbidMatchDefaultArmForEnums` |
| D5 | No bare `true`/`false` at a call site — name the argument or split the method | phpstan: own rule |

D2's payoff is concrete: a stack id and a service id are both strings, and
nothing stops you passing one where the other belongs. `StackId` and `ServiceId`
are two types, and the mistake stops compiling.

### Boundaries

| | Rule | Enforced by |
|---|---|---|
| E1 | Module kind enforcement | arch, generated from each manifest |
| E2 | `Api` is the published surface; `Internal` is unreachable | arch |
| E3 | The SDK is named in exactly one module | composer + arch |
| E4 | `Native\*` confined to `design`, `surface`, `device`, `vault` | arch: module kind |
| E5 | A listener obeys the module kinds, checked in the dispatcher rather than in the imports | test: the booted composition root |

### The module API

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

### The SuperNative surface

| | Rule | Enforced by |
|---|---|---|
| F1 | Components are thin: hold state, delegate decisions | phpstan cognitive complexity + arch size cap |
| F2 | Presenters are pure: data in, view model out, no ports injected | arch: no interface in a presenter's constructor |
| F3 | Blade holds no logic; theme tokens only; every EDGE class and tag verified | planned |
| F4 | A screen that takes a port carries `#[Lazy]`; one whose content changes while open carries `#[Poll]` | arch for the first; review for the second |

EDGE styling is **Tailwind-shaped and is not Tailwind**. There is no CSS build,
no JIT and no stylesheet to come up short. An unrecognised class is parsed,
found to mean nothing, and dropped — the screen renders, looks wrong, and says
nothing about why. `tests/Templates` drives the framework's own parser over every
template and fails on what it reports, rather than keeping a second copy of the
supported vocabulary that would silently drift from the installed package.

### Language

The application ships **`en` and `nl`**, and no more. Every other rule here is
about code a developer reads; these two are about the only text the operator
ever sees.

| | Rule | Enforced by |
|---|---|---|
| L1 | Text a person reads comes from the translator | phpstan: own rule, scoped to presenters, view models and screens |
| L2 | Every locale carries the same keys, none empty and none equal to its key | test |

**The line between the two kinds of text.** A refusal on screen, an empty state,
a notification body — a person reads these, so they are keys in
`lang/<locale>/<module>.php` and reach the screen through `__()` with a
replacement array. An exception message, a log line, a PHPStan rule's message —
a developer reads these, so they are `sprintf` and are **never** translated.
Translating a stack trace helps nobody and makes the one audience who needs it
read it in a language they did not choose.

**Why L2 is the guard that matters.** A key missing from `nl` fails nothing.
Laravel looks it up, misses, falls back to `en`, misses again, and renders the
key — so a Dutch device shows `health.unreachable` where a sentence belongs and
ships that way. Nothing else in this repository can see that, and a comparison
of the two catalogues is cheap. The reverse direction is checked too: a key in
`nl` with no `en` counterpart is a typo or text nothing shows any more.

### The rules about the rules

| | Rule | Enforced by |
|---|---|---|
| R1 | Every documented rule has an artifact carrying its identifier, and every identifier an artifact carries is documented | test |
| R2 | Every rule that claims to be enforced refuses a planted violation | test: the `Guards` suite |
| R3 | An architecture expectation names one symbol per rule, and every namespace it names resolves | arch |

**Why R2 exists.** R1 asks whether an artifact exists. It cannot ask whether the
artifact works, and the two are indistinguishable from the outside: a rule can
be documented, tagged, registered and run on every commit while permitting
exactly what it names. Three ways for that to happen are known and all three
report a green tick — an expectation naming a namespace no autoloader
registers, a list on the left of `toBeUsedIn` that is read as *uses all of
these*, and a list holding both a function name and a namespace, which cancel
out. R3 refuses those three shapes by name. R2 is the general answer: plant the
smallest violation of every rule, run the machine that enforces it, and require
it to report.

A rule with no fixture fails R2. That is the part that matters — it makes *I did
not check this one* impossible to leave implicit, which is the condition the
three above needed in order to survive.

### Comments

| | Rule | Enforced by |
|---|---|---|
| K1 | A comment states the situation and why, never the history of how it came to be | review, plus an arch check for the obvious markers |
| K2 | A docblock only where a native type cannot speak | arch |

A comment is read by someone who was not there. They cannot tell a fact from a
recollection, and the recollection is the half that goes stale — so `glob()` has
no globstar is worth writing down forever, and *we used to use glob* stops being
checkable the moment its author leaves. Rationale is welcome: why a thing is the
way it is, what it costs, what would make it wrong.

The exception is a commit message and a decision record. History is the point
there, and neither is read as a description of the current code.

### Tests

| | Rule | Enforced by |
|---|---|---|
| G1 | No mocking types you do not own — hand-written fakes for our ports | arch: no Mockery on foreign namespaces |
| G2 | Every port has one contract test, run against the real adapter **and** its fake | planned |
| G3 | No test reaches the network | `Http::preventStrayRequests()` + arch |
| G4 | No dev dependency reachable from production code | `composer-dependency-analyser` |
| G5 | One assertion idiom: Pest's `expect()`, never PHPUnit's `assert*` | arch |

**G2 is the most valuable rule on this page.** A fake that has drifted from its
adapter makes the suite green while the application is broken, and nothing else
here catches that. One contract test per port, run twice, is what makes every
fake trustworthy — and therefore what makes G1 safe to adopt.

```
tests/Contract/StackContract.php
  ✓ SdkStack    (the real adapter, against a recorded fixture)
  ✓ FakeStack   (in memory)
  — the same assertions, both times
```

### Naming and size

| | Rule | Enforced by |
|---|---|---|
| H1 | No `Manager`, `Helper`, `Util`, `Service`, `Data`, `Info` suffixes | arch |
| H2 | No `Interface`/`Abstract` affixes on type names | arch |
| H3 | Caps: methods per class, lines per method, constructor parameters, cognitive complexity | phpstan + arch |
| H4 | A test file mirrors its source file's location | arch: an orphan test fails, a class without one does not |
| H5 | A string with a value in it is built with `sprintf` — never `.`, never interpolation | phpstan: own rule, one per node type |
| H6 | An exception is named for what happened, not for being an exception | arch |

H1 is not pedantry. `BackupManager` is a name that permits anything, which is how
a class acquires twenty methods; a class you cannot name precisely is usually
more than one class.

### Runtime lifecycle

| | Rule | Enforced by |
|---|---|---|
| I1 | The runtime is **persistent**: no request-scoped assumptions, no state surviving a dispatch | arch: A6, plus review |

NativePHP runs the application as a long-lived process, not a request. A static
cache that would be harmlessly rebuilt per request on a web server here survives
between screens and becomes a stale answer on someone's phone. This is the
reason A6 is absolute rather than a preference.

---

## Patterns

### Port
An interface in `kernel`, named for what it does, not what implements it.
No `Interface` suffix, no framework types in its signature.

### Adapter
The one implementation that knows a specific outside thing. Lives in an adapter
module. Nothing depends on it; the composition root binds it to its port.

### Capability
Domain logic with ports for everything it cannot compute itself. Pure by
construction, because its kind forbids it from reaching anything else.

### Surface
Navigation and screen composition. Holds the `NativeComponent` subclasses — the
one mutable, framework-coupled shape in the codebase — and delegates every
decision to a presenter.

### Presenter
Pure. Takes data, returns a view model. No ports injected, no IO, no clock. This
is where 100% coverage and mutation testing actually land, because it is where
the decisions are.

### View model
`final readonly`, no behaviour, named for the screen it dresses.

### Outcome
A returned refusal. See C1.

---

## Refused, and why

| Anti-pattern | Why it is refused |
|---|---|
| Eloquent model as domain type | cannot be constructed without a database; property access issues IO |
| Facade | hidden global dependency; cannot be substituted by a constructor |
| `app()` / `resolve()` in a class | hides what the class needs; the constructor stops being the truth |
| Repository returning `null` | the check that gets forgotten; use an absence type |
| `array` as a domain payload | no name, no invariants, no place to put the rules |
| Generic `\Exception` | says nothing a catch block can act on |
| Mocking the SDK | encodes a guess about foreign behaviour that passes forever after it goes stale |
| `Manager` / `Helper` / `Service` | a name that permits anything, so the class accumulates everything |
| Logic in Blade | unreachable by every analyser and untestable in isolation |
| Literal colour in a template | ignores the reader's light, dark and contrast setting; fails on someone else's device |
| A second copy of the EDGE vocabulary | drifts from the installed package, silently |
| Static cache | survives a dispatch in a persistent runtime and becomes a stale answer |
| PHPStan baseline | converts today's failures into tomorrow's silence |
| `@` suppression | hides the error that was about to tell you something |

---

## Notes on temporary state

**`minimum-stability: dev`.** The root manifest allows dev stability solely
because `lemonfiber/sdk-php` has no tagged release yet, and `prefer-stable: true`
keeps everything that does have one on stable. When the SDK tags a release, this
reverts to `stable` and the constraint gets pinned with the rest of the estate.
It is recorded here so the loosening is a decision with an end, not a default
nobody revisits.
