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
bootstrap/Composition/    the composition root — the ONLY place a port meets an adapter
  NativePHP/              what exists only because of one package, including the
                          plugin allow-list NativePHP names by class
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

### How a capability gets data from a stack

The table above says an adapter may use `kernel` and the one package it adapts,
and **never a capability**. That rule and the question "how does `health` get a
report from the server?" look like they contradict each other, and the answer is
the thing worth writing down, because the first attempt at it went the wrong way
and had to be undone.

```
surface ──calls──▶ capability ──asks──▶ port (Modules\Kernel\Api)
                                          ▲
                                          │ bound once, in bootstrap/Composition/
                                          │
                                       adapter (modules/sdk, modules/device, …)
```

**Everything that crosses an adapter boundary is a kernel type.** That is what
makes the rule workable rather than a wall: `modules/sdk` reads the wire and
answers in the shared language, and a capability reads that language without
ever knowing where it came from. It is why `Problem`, `Report`, `Findings`,
`Conclusion` and `Category` live in `kernel` and not in `health` — they are the
shapes the whole application speaks, and the module that owns a shape is
whichever module everyone else has to agree with.

**A capability owns decisions, not shapes.** `health` is `WorstFirst` and
`InCategory` — the order a screen reads findings in, and how to narrow them to
one family. Those are judgements about a report and they belong to the module
named after it. The report itself belongs to the language.

The test for which one something is: *would an adapter have to name it?* If yes
it is a shape and it goes in the kernel; if no it is a decision and it goes in
the capability. `Findings` moved for exactly that reason — `modules/sdk` has to
produce one, and an adapter that named `Modules\Health` would have every adapter
free to name every capability.

### Published surface (E2)

A module exposes `Modules\<Name>\Api` and nothing else. Everything under
`Modules\<Name>\Internal` is unreachable from other modules, enforced by an arch
rule.

```
app-modules/health/src/
  Api/            ← other modules may name these
    Queries/WorstFirst.php    the order a screen reads a report in
    Queries/InCategory.php    one family of checks, and nothing else
  Internal/       ← nothing outside this module may name these
    FindingRanker.php
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
| A4 | No container-reaching helpers — `config()`, `cache()`, `view()`, `__()` and the rest — outside the composition root, `config/` and tests | phpstan `disallowed-calls` |
| A5 | `env()` only inside `config/` | arch |
| A6 | No mutable static state — a static property, and a `static` inside a method body | arch: reflection over every module class for the property, a token read over every source for the variable |
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

### Types and data shape

| | Rule | Enforced by |
|---|---|---|
| D1 | No `array` in a public `Api` signature — value objects or typed collections | arch: reflection over every published method |
| D2 | No primitive obsession: ids, tokens, durations are types | arch: no `string`/`int`/`float` parameter outside a named constructor |
| D3 | No `mixed` in public signatures | phpstan (level max + type coverage 100%) for a missing type, plus arch for `mixed` itself — coverage counts a type as declared, and `mixed` is one |
| D4 | Enums for every closed set, never string constants and never a literal compared against | arch (names) + test over source tokens (literals) + shipmonk `ForbidMatchDefaultArmForEnums` |
| D5 | No bare `true`/`false` at a call site — name the argument or split the method | phpstan: own rule |
| D6 | No unnamed numeric literal in a method body | phpstan: own rule + SonarCloud |

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
| F1 | Components are thin: hold state, delegate decisions | phpstan: `cognitive_complexity` + `H3`'s method cap |
| F2 | Presenters are pure: data in, view model out, no ports injected | arch: no interface in a presenter's constructor, over a set the same rule asserts it found |
| F3 | Blade holds no logic; theme tokens only; every EDGE class and tag verified | `tests/Templates`, against the installed parser and registries |
| F5 | Every interactive element announces itself to a screen reader | `tests/Templates` |
| F6 | Every list has an empty state | `tests/Templates` |
| F7 | No template reads a value that has one destination — a session, a credential, a stack address | `tests/Templates`, from the same table F2's surface rule counts against |
| F4 | A screen that takes a port carries `#[Lazy]`; one whose content changes while open carries `#[Poll]` | arch for the first; review for the second |
| F8 | A screen shows findings in the order a capability decided, never the order they arrived | arch: a screen that names findings names `WorstFirst` |
| F9 | A class list is written out, never decided at runtime | arch: over the text of every template |
| F10 | Every method a template calls is one its screen has, and every screen that renders is paired | `tests/Templates` |
| F11 | Every component a screen uses is classified as a control or as furniture, so F5 cannot pass over one nobody thought about | `tests/Templates` |

**Why F9 exists, given F3.** Every rule about a class list is handed the answer
of one function, `Template::classStrings()`, and that function drops any token
holding a runtime expression rather than guessing at it. It has to: with the
echo deleted, `bg-{{ $tone }}` is `bg-`, an unknown utility nobody wrote. But
what gets dropped from `class="{{ $open ? 'bg-theme-accent' : '' }}"` is the
whole attribute, and the class names inside it are then read by nothing at all.

Three rules go quiet together, because all three read that one answer. F3 stops
seeing an unknown utility, DES-R24 stops seeing a literal colour, and DES-R15
stops seeing the accent set as text. A template whose ternaries hold
`bg-theme-accnt`, `bg-red-500` and `text-theme-accent` passes every rule in
`tests/Templates` — and EDGE agrees, because it parses each of those in turn,
finds it means nothing and drops it. No error, no warning, no failed build: the
screen renders wrong on a device and says nothing about why. That is the exact
failure the vocabulary check exists to catch, so a hole in it is a rule rather
than a note.

The cure is to draw a state that changes rather than to colour it.
`how-this-stack-is.blade.php` puts the selected bar in an element of its own
under an `@if`, with a static class every check can read. The two forms that
never reach `classStrings()` at all are refused alongside — a bound `:class`,
whose value is PHP rather than a class list, and `@class([...])`, which is not
an attribute for the expression to match — because a class name hidden in either
is hidden the same way and costs the same thing.

**Why F8 is a rule rather than a note on the screen.** `WorstFirst` is the one
decision `health` makes about a report, and for two commits nothing called it. A
query nothing calls passes its own test forever — so the sorter was green, the
first findings screen rendered the envelope, and `N2-R2` was held by a test
rather than by anything an operator could see. What that produces is a list that
looks ordered: the rows are right, the words are right, and on any report whose
worst finding happened to run first the order is right as well. There is no
wrong pixel, no exception and no log line; the next report is simply in the
wrong order on somebody's phone, with a full disk above a leaking tunnel. So the
gate is on the screen rather than on the query — a screen under `Internal/Screens`
that names findings at all names `WorstFirst` too. Read over the text, because
what has to be caught is a screen that names it nowhere, and an absence has no
call site to follow to.

EDGE styling is **Tailwind-shaped and is not Tailwind**. There is no CSS build,
no JIT and no stylesheet to come up short. An unrecognised class is parsed,
found to mean nothing, and dropped — the screen renders, looks wrong, and says
nothing about why. `tests/Templates` drives the framework's own parser over every
template and fails on what it reports, rather than keeping a second copy of the
supported vocabulary that would silently drift from the installed package.

**Nothing in this suite writes the vocabulary down.** Unknown classes come from
`TailwindParser`'s own diagnostic channel, which already knows that a platform
variant aimed at the other platform is a deliberate no-op rather than a mistake.
Literal colours come from `TailwindParser::resolveColorValue`, which answers for
`red-500` and `#B91C1C` and stays silent for `theme-background` and `2xl` —
which is exactly the distinction DES-R24 turns on. Tag names come from the
element registry, the component registry, and the types the collector renders
itself. A transcribed list would keep passing through a NativePHP release that
changed any of them, which is how a rule dies without anyone noticing.

**A `bg-theme-*` token reported as unknown is a true answer, not a false
positive.** The theme resolver is provided by the application rather than by the
package. `Modules\Design\Api\Theme` is that resolver and the composition root
registers it at boot, so the two tokens this surface asserts resolve and every
other one is still reported — which is the answer rather than a gap. The
companion maps `lemon` to the accent role and `ink` to the foreground that sits
on it, and leaves the rest to the platform's own theme roles, so that the
reader's light, dark and contrast settings decide them rather than this
repository (DES-R24, DES-R26).

**Nothing about the palette is typed twice without being checked.** The two
hexes live in the `ThemeToken` enum because a module may not read a file (B3)
and a provider may not read one at boot (A9); `tests/Arch/BrandPaletteParityTest`
checks them against `app-modules/design/resources/tokens.json`, which the
hygiene gate in turn checks by digest against `brand:tokens/tokens.json`. The
brand repository makes the same arrangement for its own `tokens.css`.

### Language

The application ships **`en` and `nl`**, and no more. Every other rule here is
about code a developer reads; these two are about the only text the operator
ever sees.

| | Rule | Enforced by |
|---|---|---|
| L1 | Text a person reads comes from the translator | phpstan: own rule, over everything on the way to a screen that is not a refusal |
| L2 | Every locale carries the same keys, none empty and none equal to its key | test |
| L7 | Every catalogue key the application names is a key the catalogue holds — the literal ones read out of the sources, the derived ones asked of each enum that builds them — and every line the catalogue holds is one something shows | test: three, one per direction plus one for derived keys |

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

**Why L7 exists beside it.** L2 compares the catalogues against each other, so
it is blind to the case where they agree and are both wrong: a key that is in
neither, because somebody mistyped it at the call site or renamed the line and
not the reader. That renders the key on every device in every locale. L7 reads
the keys out of the source — production PHP and Blade alike, since a template is
not PHP any analyser reads — and asks the catalogue for each one.

The cure is usually not a corrected literal. Where a key belongs to a closed set,
derive it from the case: `Permission::reason()` builds `device.camera_reason`
from `Permission::Camera`, so there is one spelling, and the rule that checks the
catalogue is checking the string the application actually uses. A literal at the
call site is a second spelling, and the second spelling is the one that drifts.

### What the analyser cannot see

Every other rule here depends on the analyser being able to read the code. These
are the constructs that take something out of its view, and each one disables
every rule that would otherwise have applied to whatever it exposes.

| | Rule | Enforced by |
|---|---|---|
| P1 | No `__get`, `__set`, `__call`, `__callStatic` — `__invoke` stays | phpstan: own rule |
| P2 | No variable variables and no dynamic class, method or property name | phpstan: own rule |
| P3 | No `func_get_args()`, no `#[AllowDynamicProperties]` | phpstan `disallowed-calls` |
| P4 | No reflection in production code | phpstan `disallowed-calls`, scoped by path |

### The runtime is one long-lived process

`Runtime::boot()` runs once and `Runtime::dispatch()` handles every interaction
after it. Nothing between two screens resets. That is why these are stricter
here than in a web application, where the same calls are undone by the process
ending a few milliseconds later: here a change made on one screen is still in
force on the next one, and on the one after that, until the operator force-quits
an application they have no reason to think is broken.

| | Rule | Enforced by |
|---|---|---|
| Q1 | No runtime configuration mutation — `ini_set`, `setlocale`, `date_default_timezone_set` and their neighbours | phpstan `disallowed-calls` |
| Q2 | No superglobals | phpstan `disallowed-superglobals` |
| Q3 | No `static::` or `new static()` — every class is final | phpstan: own rule |
| Q4 | No `echo`/`print` from a module | phpstan: own rule |

### Text a person reads

The application ships two locales, so every one of these is a bug that exists
today rather than a precaution against one.

| | Rule | Enforced by |
|---|---|---|
| L3 | Multibyte-safe string functions only | phpstan `disallowed-calls` |
| L4 | No date formatted by a literal format string | phpstan `disallowed-calls` |
| L5 | No number formatted with separators written into the call | phpstan `disallowed-calls` |
| L6 | No byte-order sorting of text a person reads | phpstan `disallowed-calls` |

`strlen` counts bytes. A Dutch service name with an accent in it is longer in
bytes than in characters, so truncating one with `substr` splits a character and
the screen renders a replacement glyph. Dutch writes `1.234,5` where English
writes `1,234.5`. Byte-order sorting puts every accented character after `z`.
None of these is theoretical once the second locale exists.

### Security and supply chain

| | Rule | Enforced by |
|---|---|---|
| S1 | The dangerous, execution, insecure and non-timing-safe call bundles are on | phpstan `disallowed-calls`, four shipped bundles |
| S2 | No package with a published advisory resolves | `roave/security-advisories` + `composer audit` |
| S3 | TLS verification is never weakened | phpstan: own rule (array items) + `disallowed-calls` (the call and curl forms) + test over `config/` and every `.env*` (N1-R21 — the flag may not exist) |

**Q3 and Q4 are about shapes that belong to a different codebase.** Late static
binding resolves to the class it is written in, because every class here is
final and there is no subclass for it to find — what it actually does is tell
the next reader that one exists. And there is no output stream: the runtime
publishes a binary element tree, so an `echo` produces a malformed frame rather
than a visible mistake, diagnosed on a device with no console.

**Why S3 is a rule and not a review note.** ADR-0018 pins a stack's certificate
by a fingerprint taken from the pairing material, which is what makes a stack on
a home network safe to talk to without a public certificate authority. Every
spelling of the verify-off switch — `'verify' => false`, `'verify_peer' =>
false`, `'allow_self_signed' => true`, `CURLOPT_SSL_VERIFYPEER => 0` — is one
line in an options array that reads like configuration. It does not relax the
check; it removes the only one there is, and leaves the pin being compared by
code nothing reaches.

`roave/security-advisories` is a conflict-only package: it carries no code and
fails resolution when a dependency matches a published advisory, so the failure
arrives at `composer update` rather than at `composer audit` in CI a week later.

### Where things go

```
bootstrap/Composition/    the composition root, and nothing else
app-modules/<name>/
  composer.json           declares the module's kind, which generates its rules
  src/Api/                what other modules may name
  src/Api/Commands/       one public method each, returning Outcome
  src/Api/Queries/        one public method each, never returning Outcome
  src/Internal/           unreachable from anywhere else
  src/Internal/Presenters/   pure: data in, view model out
  src/Internal/ViewModels/   what a template reads, deciding nothing
  resources/views/        the screens this module navigates to
  tests/                  mirroring src/, one directory level for one
lang/<locale>/<module>.php   every sentence a person reads
tests/Arch/               the rules
tests/Templates/          Blade, which no analyser reads
tests/Contract/           one suite per port, run against the adapter and the fake
tests/Feature/            the composition root
tests/Guards/             every rule, shown to refuse a violation
tests/Support/            what the four above share
phpstan/Rules/            the rules that are easier to write than to find
```

| | Rule | Enforced by |
|---|---|---|
| W1 | `bootstrap/` holds no class but the composition root | arch |
| W2 | A module's `src/` declares only its own namespace | arch |
| W3 | Root `tests/` holds only the suites; root `resources/views/` holds no Blade | arch |
| W4 | A module's tests are namespaced for that module | arch |
| W5 | A file with no namespace imports no global name — the warning it raises fails the run silently | arch |
| W7 | Every reader puts its envelope through the wire gate before reading the payload | arch: over the SDK module's own sources |

**These are not tidiness.** Every rule on this page is derived from a path or a
namespace: the kind rules read `app-modules/<name>/composer.json`, the published
surface rule reads `Api` against `Internal`, H4 pairs a test with its source by
replacing one path segment. A file in the wrong place is a file the rules
governing its neighbours do not reach — and nothing says so, because a rule that
finds no files reports a green tick.

`bootstrap/Composition/` is the sharpest case. The permission to name both a port
and an adapter is granted by path, along with an exemption from A2, A3 and A4, so
a class put there acquires all of it without anyone deciding it should. `W1` is
what keeps the rest of `bootstrap/` free of classes, and there is no `app/`: the
one class NativePHP names as `App\Providers\NativeServiceProvider` is mapped
into `bootstrap/Composition/NativePHP/Admitting/` by PSR-4, because the vendor
fixes the class name and not the path.

### The rules about the rules

| | Rule | Enforced by |
|---|---|---|
| R1 | Every documented rule has an artifact carrying its identifier, and every identifier an artifact carries is documented | test |
| R2 | Every rule that claims to be enforced refuses a planted violation | test: the `Guards` suite, run on its own |
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

**A fixture must break the rule's sentence, not its mechanism.** This is the
failure R2 is most likely to miss, because a fixture written from the code that
enforces a rule passes by construction.

`D3` says "no `mixed` in public signatures". Its fixture planted a parameter
with *no type at all* and asserted the analyser reported `missingType.parameter`
— which it does, and which proves something true about missing types and nothing
at all about `mixed`. Type coverage counts whether a type is declared, and
`mixed` is a declared type; `mixed $said): mixed` is 100% covered by that
measure and legal at level max. The rule read as enforced, R2 read as satisfied,
and a published signature saying `mixed` would have passed every gate.

Write the fixture from the sentence. If the sentence cannot be broken in a way
the mechanism sees, that is the finding: the mechanism is narrower than the rule
and one of the two has to move.

**Every fixture that is a file sits under a directory called `Fixtures`**, with
one exception: the coverage report a floors fixture needs goes to `coverage/`,
which is generated output and wholly ignored already. For the length of a
run the files are really on disk, so `.gitignore` and `pint.json` both exclude
that one name. Without it, a commit made beside a run picks up a deliberate rule
violation, `git status` reports a dirty tree that is about to clean itself, and
the formatter fails on files whose whole purpose is to be wrong. The convention
costs a directory name: a `Fixtures/` directory anywhere in this repository
belongs to the harness and is never committed.

**Some violations are not a file.** A second accessor on a type that already
exists, a listener registered against another module's event, a `@param` that
stops handing on what it read — each is a change to a file this repository owns,
and for a long time each was recorded as *nothing can break this* with a
paragraph explaining that the harness could only write whole files. That was an
honest answer to the wrong question: what could not be done was the editing, not
the breaking.

`Fixture::edit` does it. It finds one piece of text in a real file and puts
something else in its place, and it refuses a piece of text it cannot find
exactly once — a fixture that matched nothing would leave the rule passing on an
unedited tree, which is the vacuous green this whole harness exists to make
impossible. What makes it safe to run against files that are committed is the
same manifest the sweep already kept: every write records what it replaced, so
an edited file goes back exactly as it was after a failure, an exception or a
kill, and the by-name pass puts the text back directly for the run where the
manifest is itself what went missing. The earliest record for a path wins, so
two fixtures editing one file still restore it to what was there before the run
began.

**Some violations are the run itself.** R2's own violation is a documented rule
with nothing planted under it — and planting that would mean leaving a rule
uncovered in this repository, where the run that reads it is this run. There is
no file, and there was no edit either, so R2 was recorded as *nothing can break
this* for the same reason the paragraph above was wrong: what could not be done
was the planting, not the breaking.

`Fixture::direct` is the answer. Split the judgement from the reading around it
— `rulesWithNoFixture` takes the claims and the coverage as arguments rather
than going and finding them — and it can be handed the violation on every run,
which is the whole of what a planted file buys. The test that does so asserts
the empty answer *and* a non-empty one, because everything a check says about
the clean case is equally true of a function that returns nothing whatever it is
asked.

`Q-R66 (discovery)` is the same shape and the worse failure. Every path here is
built from `Tree::root()`, which is `dirname(__DIR__, 2)` — correct for exactly
as long as that file stays two directories down. A root that has drifted cannot
be planted against, because the fixture would have to be written to a tree the
harness could no longer find; and it does not announce itself either, since
`filesUnder` answers `[]` for a directory that is not there and every rule built
on it then reads no files and reports nothing wrong. `Tree::isTheRepository`
is the judgement taken out of `root()`, so it can be asked about the parent
directory — which is precisely what a file moved one level down would produce —
and watched refusing it on every run.

`G11` is the third, and it shows the shape is not rare. The rule reads
`phpunit.xml` for the attributes that make a diagnostic fail the run, and the
settings it reads belong to the run doing the reading — so taking one out to
plant a violation changes *that* run rather than a fixture. `notTurnedOn` was
already a pure function taking the declared settings and the wanted list; it had
simply never been asked about a file where something was missing. Now it is,
including the two shapes PHPUnit treats alike and a reader does not: an attribute
deleted, and an attribute switched off on purpose.

`N1-R13` is the fourth, and it is the one where the split found something. The
rule compares five enums against the unions the generated envelope declares, and
the envelope is somebody else's file in `vendor/` — restored by composer rather
than by this harness, so a fixture that failed to clean up would leave the
installed SDK wrong. `unionIn` and `outcomesIn` now take the text rather than
going and finding it, and what that exposed was a `[]` nobody had watched them
return: a field declared twice with unions that disagree is a question the reader
cannot answer, and it answers by finding nothing rather than by taking whichever
came first. That path is the difference between comparing an enum against half a
contract and refusing to compare at all, and until now it had only ever been
described in a comment.

**The `Guards` suite runs alone.** `composer test` is `pest --parallel` with
`Guards` excluded; `composer test:guards` runs it by itself. The harness plants
a violation of every rule into the working tree, so a process reading that tree
beside it sees files appear and vanish mid-run — which is a failure that looks
like anything except what it is. Everything else is deterministic in parallel
and is checked that way.

A rule with no fixture fails R2. That is the part that matters — it makes *I did
not check this one* impossible to leave implicit, which is the condition the
three above needed in order to survive.

### Comments

| | Rule | Enforced by |
|---|---|---|
| K1 | A comment states the situation and why, never the history of how it came to be | review, plus an arch check for the obvious markers |
| K2 | A docblock only where a native type cannot speak | arch |
| K3 | A docblock says a thing once; a paragraph repeating another is a copy that goes stale | arch |
| K4 | A docblock describes a symbol, never another docblock | arch |

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
| G2 | Every port has one contract test, run against the real adapter **and** its fake | test: the ports, their implementations and the contract file, compared |
| G3 | No test reaches the network | `Http::preventStrayRequests()` + an empty global `MockClient` + test |
| G4 | No dev dependency reachable from production code | `composer-dependency-analyser` |
| G5 | One assertion idiom: Pest's `expect()`, never PHPUnit's `assert*` | arch |
| G6 | No committed `->only(`, and no `->skip()` whose last argument is not the reason | arch |
| G7 | Every module declares its own coverage and mutation floors | arch |
| G8 | Every port in `Modules\Kernel` is bound, once, in the composition root | test: the booted composition root |
| G9 | No module is below the coverage floor it declared | test: the `Floors` suite, over the clover report |
| G10 | No two test files declare the same helper or file-level constant name | arch: over the text of the test files |
| G11 | A diagnostic fails the run, and no setting exempts one | arch: the settings, read out of `phpunit.xml` |
| G12 | A suite standing a payload in for a stack reads it against the contract | arch: over the suites that write a wire body |

**G2 is the most valuable rule on this page.** A fake that has drifted from its
adapter makes the suite green while the application is broken, and nothing else
here catches that. One contract test per port, run twice, is what makes every
fake trustworthy — and therefore what makes G1 safe to adopt.

**There is a half G2 cannot reach, and `G12` is it.** Running both
implementations against the same assertions proves they agree with each other.
It does not prove either agrees with the stack, because the payload they are
both run against is written by hand — by whoever wrote the reader. When the
reader looks for a field at a path the contract has not got and the payload
obliges, both sides pass and the application is broken against every real
machine.

That is not a hypothetical. `Standings` read `state` and `running` off the top
of the `update` payload; the contract puts the first under `changelog` and
gives the top-level one another meaning. Three rules passed, and the screen
would have refused every stack with an update waiting. So the payload is now
read against the generated types instead of against the reader — a key the
contract has not got there, a key it requires that the payload leaves out, and
a word outside a closed set it declares. The third is the one that names a
defect rather than a symptom: a reader in the wrong place often finds a field
that *exists* there under another meaning, so nothing is unknown and nothing is
missing, and only the word is wrong.

`WhatTheContractDeclares` reads the types and `WhatTheContractAccepts` judges a
payload against them. The rule is over the suites rather than inside one: a
check living in whichever suite last remembered would have `G12` claim a
guarantee that one file's assertion was carrying, which is the same defect one
level up. A suite is found by `api_version`, which nothing but an envelope
writes, and the rule fails if that mark ever matches nothing at all.

```
tests/Contract/ClockContractTest.php
  ✓ SystemClock   (the real adapter, reading the platform's clock)
  ✓ FrozenClock   (in memory, in tests/Support/Fakes)
  — the same assertions, both times
```

The file is named for the port, ends in `Test.php` because that is the suffix
PHPUnit collects, and is found by the rule from either end: a port with no
contract fails, and so does an implementation the contract does not name. Fakes
live in `tests/Support/Fakes` rather than in a module, so that nothing
reachable from production is a fake (`G4`).

### Naming and size

| | Rule | Enforced by |
|---|---|---|
| H1 | No `Manager`, `Helper`, `Util`, `Service`, `Data`, `Info` suffixes | arch |
| H2 | No `Interface`/`Abstract` affixes on type names | arch |
| H3 | Caps: methods per class (20), cognitive complexity | phpstan: own rule + `cognitive_complexity` |
| H4 | A test file mirrors its source file's location | arch: an orphan test fails, a class without one does not |
| H5 | A string with a value in it is built with `sprintf` — never `.`, never interpolation — and a message is one literal, never two joined by a dot | phpstan: own rule, one per node type |
| H6 | An exception is named for what happened, not for being an exception | arch |
| H7 | A test is named and described for the behaviour it pins | arch |
| H8 | A method returns from at most three places | phpstan: own rule |

**What `H3` does not cap, and why.** This row read *methods per class, lines
per method, constructor parameters, cognitive complexity* for a long time, and
only the last of the four had anything counting it — so a class could pass here
and be refused by SonarCloud under `Q-R64`, which is how `WhatWouldBePutRight`
reached twenty-one methods before anybody heard about it. The method count now
has a rule of its own, at SonarCloud's own number so the two cannot disagree.

The other two were dropped from the sentence rather than given mechanisms,
because both would refuse code that is right as it is. A cap on method length
would name `CompositionRoot::register` and `OperatorServiceProvider::boot`,
which are lists of bindings with a paragraph each on why — length is what a
reader wants there, and what makes a long method hard to follow is already
capped as cognitive complexity. A cap on constructor parameters would name the
row carriers: `WhatOneServiceSays` takes ten because a service has ten facts a
template renders, and `D1` refuses the array that would hide them behind one.
Splitting a row in half to satisfy a count makes two halves a template has to
join back up.

A rule whose sentence is wider than its mechanism is worse than a narrow rule
honestly described: the table reports green and a reader stops checking, which
is strictly worse than an unchecked area, because an unchecked area gets
reviewed by a person.

**Why the floors are per module.** One percentage across twelve modules is an
average, and an average is true about what it covered and silent about what it
covered over: a capability at 100% carries an adapter at 40% and the gate
reports a pass. The clover report already holds the per-file numbers, so
splitting it by directory costs nothing at the point of measurement and turns
one number into twelve.

**There is deliberately no default floor.** A module that declares none fails
G7 by name. A default would put the number back where nobody chose it, and a
module added tomorrow would inherit a bar somebody picked for a different
module — which is the silent exemption the change exists to remove. The kind's
convention is named in the failure message instead, so declaring it is a
ten-second job rather than a guess.

**The ratchet is on the declared floors, not the measured ones.** The obvious
rule — a floor tracks actual coverage and may only rise — is a gate that blocks
its own cure: a module gaining a well-tested class raises its real coverage
without anyone deciding to, and the build turns red for an improvement. Declared
floors move only when somebody edits a manifest, so the total can never be
tripped by code getting better. The slack between a floor and the real number is
printed every run and argued down in review.

**Mutation floors are declared in the same place and cannot be read the same
way.** There is no machine-readable mutation report — Pest offers `--min`, which
fails a run, and nothing that emits a score. So the floors are enforced by
invocation: modules sharing a floor share one run, because a floor of 100 admits
no offsetting between them, and a module whose floor differs gets its own. The
path list is generated from the manifests rather than written out, which is what
stops it going stale the day a module is added.

**Why G6 is worth a rule of its own.** A committed `->only()` makes Pest run
that one test and report green. Every other rule on this page stops holding, the
run says nothing is wrong, and the change that did it is one word long. It is the
single most expensive thing that can be committed here.

**G11 is G6's other half, and it is two settings rather than one.** `->only()`
stops the suite reporting; a diagnostic nothing acts on lets it report and be
ignored. `failOnWarning` and its neighbours are what act — and on their own they
are narrower than they read, because PHPUnit's issue filter drops a warning,
notice or deprecation raised inside `@` before the result is assembled. The run
prints the diagnostic, counts it in the summary, and exits zero: the number a
reader sees and the number the gate reads are not the same number. Most of what
a framework raises at boot is raised under `@`, so that is not the rare case, it
is the usual one. `ignoreSuppressionOf*` on `<source>` is what puts the
suppressed ones back in front of the `failOn*` attributes, and G11 requires both
halves because either alone reads like a gate and is not one.

The settings only make PHPUnit *report* what PHP raised anyway. Nothing about
them changes what the application does, and `@` suppresses in production exactly
as it did before.

**Why D6 permits 0, 1 and 2.** Their names would be the number. Everything else
— a thirty-second timeout, a three-attempt budget, a staleness threshold in
seconds — is a decision an operator can feel, and a decision that lives as a
literal cannot be found by searching for what it means. Only method bodies are
read, so moving the number to a class constant or an enum case is both the cure
and the exemption.

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

It is also the reason A6 is two readings rather than one. A static property is a
fact about a class and reflection answers it; a `static` inside a method body has
no property, no name the class knows and no entry in any API, and it outlives a
dispatch in exactly the same way. Memoising inside a method is the natural way to
write a cache, so the declaration reflection cannot see is the one somebody
reaches for first.

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

`final readonly`, named for the screen it dresses. It carries what a template
reads and folds nothing: every value on it was decided by the presenter beside
it, which is what keeps those decisions somewhere a mutation run can reach.

**No behaviour means no decisions, not no methods.** Asking a view model about
what it already holds — whether a field is set, whether an identity matches the
one a screen is acting on — is reading. The line is the fold: a method that
takes a domain value and works out what to show is a presenter's, wherever it
happens to be written.

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
