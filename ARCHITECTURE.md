# Architecture

How this app is put together, which patterns it uses on purpose, and which
shapes are refused. Most of what is below is a test in `tests/Arch`; where it
cannot be, it says so.

The short version: **ports and adapters, sliced by feature, with every view
dumb and every boundary faked in tests.**

---

## The dependency rule

One direction, and it does not bend.

```
resources/views/companion/**      Blade + EDGE. Reads its component. Nothing else.
        │
        ▼
app/Companion/<Feature>/          Components and presenters. The app's behaviour.
        │
        ▼
app/Contracts/                    Ports. Interfaces and value objects only.
        ▲
        │
app/Adapters/                     The only code that touches anything outside.
```

- **Features** depend on `Contracts` and `Support`. Never on `Adapters`, never on
  the SDK, never on NativePHP's bridges.
- **Contracts** depend on nothing but PHP and their own value objects. No Laravel,
  no Saloon, no framework of any kind. This is what makes them cheap to fake.
- **Adapters** depend on whatever they wrap, and nothing depends on them except
  the container binding that installs them.
- **Views** depend on nothing. They read what their component exposes.

Arrows point inward. An adapter knowing about a feature is the mistake this rule
exists to prevent, because it is how a boundary quietly stops being one.

## Two boundaries, and only two

Everything outside this application reaches it through one of these.

### `Contracts\Stack` — lemonfiber

One port per capability the app needs of the stack: health, lifecycle, requests,
logs. The **only** implementation is `Adapters\Stack\Sdk*`, which is the only
code in the repository permitted to name `Lemonfiber\Sdk` (`N1-R16`).

A feature asks `StackHealth::read()` and gets a value object. It does not know an
envelope exists, which is what stops the contract's shape leaking into thirty
screens.

**When the SDK cannot answer something, the port is not written.** The gap is
raised and the work stops (`N1-R17`). Do not add a port whose adapter would have
to reach around the SDK to implement it.

### `Contracts\Device` — the phone

One port per native capability: secure storage, biometrics, camera,
notifications. Implementations live in `Adapters\Device` and are **as thin as
they can be** — a method that calls the platform and returns, with no branching
worth testing.

That thinness is what earns the coverage exclusion. See below.

## The patterns, named on purpose

### Component

A NativePHP component: holds the state a screen needs, exposes it to the view,
and handles presses. It is **thin** — it calls a port, hands the answer to a
presenter, and stores the result.

It does not format, it does not decide, and it does not talk to anything except
ports and presenters. A component with business logic in it is the anti-pattern
this whole layout exists to avoid, because it is the one class that is hardest to
test and easiest to grow.

Components are the one exception to `readonly` — the renderer reads their state
on re-render, so they must be mutable. The exemption is named in the arch test
rather than left as a gap.

### Presenter

A **pure function** from a port's value object to a view model. No I/O, no
container, no clock, no randomness — arguments in, view model out.

This is where the interesting logic lives, which is deliberate: it is also the
easiest thing in the codebase to test exhaustively. **100% coverage and 100%
mutation score land here**, and they mean something because a presenter has no
dependencies to mock.

Ordering findings worst-first, deciding that a value is stale and how stale,
turning a `Problem` into a headline and a remedy — all presenter work.

### View model

A `final readonly` object the view reads. Public properties, no methods beyond
simple accessors, no behaviour. If a view model needs a method to decide
something, that decision belongs in the presenter.

### Port and adapter

An interface in `Contracts`, an implementation in `Adapters`, a fake in
`tests/Fakes`. Features are tested against the fake; the adapter is tested
against the real thing where that is possible and excluded where it is not.

### Outcome, not exception

Expected failures are values. The stack being unreachable, a credential being
refused, a repair being declined — these are outcomes a screen renders
(`N1-R10`), not exceptions to catch at a boundary.

Exceptions are for programmer error and nothing else.

## Feature slices

A feature is a directory holding everything one job needs:

```
app/Companion/Health/
    HealthComponent.php        the screen's state and presses
    HealthPresenter.php        pure: StackHealth -> HealthView
    HealthView.php             final readonly view model
resources/views/companion/health.blade.php
tests/Feature/Health/...
```

Not layered by type across the app — `app/Presenters`, `app/Components` and so on
put every feature's pieces as far apart as they can be, and make "what does
Health touch?" unanswerable without a search. A slice answers it by being a
directory.

A slice may use `Support` and `Contracts`. **A slice may not use another slice.**
Where two need the same thing, it moves to `Support` or becomes a port.

## Refused shapes

Each of these is a test unless marked otherwise.

| Anti-pattern | Why it is refused |
|---|---|
| **Facades in a feature** | `Illuminate\Support\Facades\*` hides a dependency the constructor should have declared and makes a test reach for a framework. Inject the port. |
| **Service location** | `app()`, `resolve()`, `Container::` in a feature — the same hiding, with an added runtime failure mode. |
| **The SDK outside its adapter** | `N1-R16`. The whole boundary in one rule. |
| **Any HTTP client** | Guzzle, cURL, `file_get_contents` on a URL. There is exactly one way out and it is the SDK. |
| **A web view** | `<native:webview>`. ADR-0017's decision, enforced rather than remembered. |
| **Logic in a view** | `@php` blocks, or a view calling anything but its component's accessors. |
| **Static mutable state** | Static properties that are not constants. Shared state across a re-render is a bug that only appears on a device. |
| **`env()` outside `config/`** | Returns null once config is cached, so it works in development and fails in a build. Laravel's own rule; worth enforcing rather than remembering. |
| **Debug leftovers** | `dd`, `dump`, `var_dump`, `ray`, `print_r`. |
| **Silenced errors** | The `@` operator. A suppression without a reason is a decision nobody recorded. |
| **A cross-slice reference** | Feature A naming feature B. Promote the shared thing or make it a port. |
| **A literal colour** | `DES-R24`. Theme tokens only, or light and dark quietly diverge. |
| **Analytics** | `N4-R12`, denied at the dependency level so it cannot arrive transitively. |
| **A God component** *(guard, not arch test)* | Component classes are capped on length and public method count. Past the cap, the logic belongs in a presenter. |

## Testing shape

| Layer | Tested how | Bar |
|---|---|---|
| Presenter | Direct, no doubles | 100% coverage, 100% mutation |
| Component | Against fake ports | 100% coverage |
| View | Rendered, asserted on output | Covered; excluded from mutation |
| Adapter — stack | Against the SDK's own test transport | 100% coverage |
| Adapter — device | Not run in CI | **Excluded by name, with the reason, and a test that the list does not grow** |

That last row is the only place "100%" is qualified, and it is qualified in one
file rather than by a floor below 100. An exclusion list that can grow silently
is a coverage gate that means nothing by its second year.

## Where the specification lives

Requirements are in [area N](https://github.com/lemonfiber/spec/tree/main/10-functional/features/n-companion);
the reasoning is in [ADR-0017](https://github.com/lemonfiber/spec/blob/main/00-overview/decisions/0017-the-companion-app-as-a-fourth-surface.md).
This document is how the code is arranged. Where the two disagree, the
specification wins and this file is wrong.
