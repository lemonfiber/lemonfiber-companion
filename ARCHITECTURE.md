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
  services/               what a stack runs, kept between launches
  requests/               what the household asked for, kept between launches
  health/                 verdict, findings, repairs          (N2)
  backups/                snapshots
  updates/                versions, apply, undo

  wayfinding/             top bar, stacks list, menu, tabs    (N28)
  operator/               navigation + screen composition     (N2)
  household/              navigation + screen composition     (N3)

  sdk/                    the only module that calls the SDK  (N1-R16)
  device/                 permissions, notifications          (N4)
  vault/                  secure storage, app lock            (N4)
  codes/                  QR codes another phone can scan
  seal/                   sealing what the phone keeps

  store-kit/              what every store of readings does over its table

  dx/                     stand-ins for a stack, require-dev only
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
| `capability` | `kernel`; Illuminate and a store kit in its own store only | Illuminate or a store kit outside its store, Native, the SDK, other capabilities, adapters, surfaces |
| `design` | `kernel`, `Native\Mobile` | the SDK, capabilities, surfaces |
| `surface` | `kernel`, `design`, capabilities, `wayfinding`, `Native\Mobile` | the SDK, adapters, the other surface |
| `wayfinding` | `kernel`, `design`, capabilities, `Native\Mobile` | the SDK, adapters, surfaces |
| `adapter` | `kernel`, the one package it adapts | capabilities, surfaces, other adapters |
| `stand-in` | `kernel`, adapters, any outside package | capabilities, design, surfaces |
| `store-kit` | `kernel`, `Illuminate\Database` | the rest of Illuminate, Native, the SDK, capabilities, adapters, surfaces |

Two consequences worth stating plainly:

**A capability module cannot be run wrong.** It has no framework, no network and
no clock of its own, so a test of it is a unit test whether or not anyone
intended one. The one place in it that names the framework is its store, which
nothing else in it can reach but through a port: see A1, A7 and A11.

**`modules/sdk` is the only shipped manifest that requires `lemonfiber/sdk-php`.**
N1-R16 therefore stops being a rule a reviewer enforces and becomes a fact the
dependency resolver enforces: a surface module that types `Lemonfiber\Sdk` fails
`composer-dependency-analyser` because its own manifest does not require it. The
one other manifest that requires it is `modules/dx`, the stand-in, which the root
installs under `require-dev` and a release therefore does not contain.

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
    Queries/WorstFirst.php                 the order a screen reads a report in
    Queries/InCategory.php                 one family of checks, and nothing else
    Queries/TheCauseBeforeItsSymptoms.php  findings that share a cause, cause first
  Internal/       ← nothing outside this module may name these
    WhatExplainedIt.php                    the check that explains a finding, as a name
    HealthReadingsKept.php                 the port its decisions ask of what it keeps
    Store/        ← nothing but the composition root may name these, inside the module or out
      HealthReadingsInTheDatabase.php      that port, over the app's own database
app-modules/health/database/migrations/    the tables it keeps them in, named health_*
```

The benefit is refactoring: anything in `Internal` can be renamed, split or
deleted without reading another module, because nothing outside can be pointing
at it. That guarantee is worth more than the one directory it costs.

---

## The rules

Each rule is enforced somewhere. Where a rule is not yet enforced
mechanically, it says so — an unenforced rule is a wish, and labelling it
honestly is better than pretending.

The rules live in `.docs/architecture/`, one file to an area, and
`tests/Arch` reads their tables with this file's:

- [Framework, errors and boundaries](.docs/architecture/framework-and-boundaries.md): framework coupling, the untestable primitives, errors and control flow, types and data shape, boundaries, and the module API
- [The SuperNative surface](.docs/architecture/the-supernative-surface.md): components, presenters and templates
- [Language, runtime and security](.docs/architecture/language-runtime-and-security.md): the language, what the analyser cannot see, the long-lived runtime, text a person reads, and security and the supply chain
- [Where things go](.docs/architecture/where-things-go.md): the tree, and what each directory holds
- [The rules about the rules](.docs/architecture/the-rules-about-the-rules.md): how each rule is carried and proven, and comments
- [Tests](.docs/architecture/tests.md): what a test may do, and what the suites hold
- [Naming, size and lifecycle](.docs/architecture/naming-size-and-lifecycle.md): naming and size, and the runtime lifecycle

---

## Patterns

### Port

An interface in `kernel`, named for what it does, not what implements it.
No `Interface` suffix, no framework types in its signature. A port for what a
capability keeps is the one exception to where it lives: the capability
declares it in its own `Internal`, because nothing but its own decisions asks
it.

### Store

The one class that answers a capability's store port, under that capability's
`src/Internal/Store`, over Laravel's query builder with an injected connection:
one private, named method per query and one place a row becomes a value. It is
handed only what is sealed, and bound to its port by the composition root.

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
