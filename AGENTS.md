# Working in `lemonfiber-companion`

The mobile companion app: a fourth surface for lemonfiber, built with NativePHP
in SuperNative mode. Read this before changing anything.

The specification is [area N](https://github.com/lemonfiber/spec/tree/main/10-functional/features/n-companion)
and [ADR-0017](https://github.com/lemonfiber/spec/blob/main/00-overview/decisions/0017-the-companion-app-as-a-fourth-surface.md).
This file is how to work here; the spec is what to build.

---

## The one rule that is different from everywhere else

**The SDK is the only way out, and a gap in it is a question rather than a
workaround.**

Every call to lemonfiber goes through `lemonfiber/sdk-php`. This app issues no
HTTP request of its own, builds no URL, and parses no envelope the SDK did not
hand it (`N1-R16`).

Where the SDK does not expose something a screen needs, **that is where the work
stops** (`N1-R17`). Raise the gap against the SDK and the contract, say what is
blocked, and move to something else. Do not:

- reach past the SDK to the endpoint, even once;
- re-implement the call beside it;
- approximate the answer from a neighbouring endpoint;
- add an HTTP client to `composer.json`.

A blocked screen is a smaller problem than a fourth consumer the contract does
not know it has. **Being blocked is a finding worth reporting**, not a problem to
solve locally. If you are an agent working unsupervised, stop and say so.

An architecture test enforces the first half. The second half is judgement, which
is why it is written here in the first section rather than buried.

---

## What this app is, in one paragraph

A rendering of the core's answers. It decides nothing the core does not already
decide (`G1-R2`). One application serves both the operator and the household, and
**the credential that signs in decides which** — there is no setting and no second
build (`N3-R1`). What a household member may do is the core's answer, rendered;
this app holds no permission model of its own (`N3-R2`).

## What it must never do

| | |
|---|---|
| Add a web view | `<native:webview>` is forbidden. ADR-0017's central decision; an arch test enforces it. An exception has to arrive as a spec change, not a pull request. |
| Put logic in a view | A Blade view reads from its component and nothing else. No HTTP, no queries, no decisions. |
| Assert a literal colour | Colour comes from `bg-theme-*` / `text-theme-*` tokens. A literal is a dark-mode bug that ships (`DES-R24`). |
| Ship analytics | No telemetry, no third-party crash reporting, ever (`N4-R12`). A dependency test denies the known SDKs so one cannot arrive transitively. |
| Store a session anywhere but the platform's secure storage | Keychain or Android Keystore. Never preferences, never a file, never an unencrypted backup (`N4-R5`). |
| Request a permission on launch | Every permission is asked for at the point of first use, with a reason, and the app works without it (`N4-R1`, `N4-R3`). |

## Styling is EDGE, and EDGE is not Tailwind

Markup looks like this:

```blade
<native:column class="w-full h-full p-4 gap-4 bg-theme-background">
    <native:text class="text-2xl font-bold">Stack health</native:text>
    <native:button label="Run checks" @press="check" />
</native:column>
```

The class vocabulary is **Tailwind-shaped and defined by EDGE**, compiled to
SwiftUI and Jetpack Compose. There is no CSS build, no JIT, and nothing that
errors on an unknown class — a typo'd `bg-theme-backgrond` compiles happily and
renders nothing.

So `composer guards` validates every class and every `<native:*>` tag against the
vocabulary the installed EDGE package actually defines. If you invent a utility,
the build fails. That check is the only thing standing between a typo and a blank
screen, so do not weaken it.

**Do not reach for Tailwind's tooling.** No `tailwindcss` dependency, no
`prettier-plugin-tailwindcss`, no PostCSS. None of it applies and all of it would
mislead the next reader.

## The gates

`composer ci` runs everything CI runs. Run it before you push.

| Gate | Command | Bar |
|------|---------|-----|
| Format | `composer lint` | Pint, `per` preset, strict rules. `composer lint:fix` writes. |
| Static analysis | `composer analyse` | PHPStan `level: max` + Larastan + strict + deprecation + ergebnis `allRules` + 100% type coverage. **There is no baseline file and one must not be added.** |
| Dead idioms | `composer refactor` | Rector dry-run. `composer refactor:fix` writes. |
| Repository guards | `composer guards` | EDGE vocabulary, forbidden dependencies, permission purpose strings. |
| Dependencies | `composer deps` | Unused and shadow dependencies. Plus `validate --strict`, `normalize`, `audit`. |
| Tests | `composer test:coverage` | 100% line coverage. |
| Mutation | `composer test:mutation` | 100% on logic. Views are excluded, by decision, with the reason beside the exclusion. |

### Why coverage can be 100% when some code only runs on a device

Every native capability sits **behind an interface** with a fake for tests. The
thin adapter that actually calls the platform is the only excluded code, it is
listed by name in `phpunit.xml` with its reason, and a test asserts that list
does not grow silently.

If you need a new native capability: write the port, write the fake, test against
the fake, and keep the adapter as thin as it can be. If you find yourself wanting
to exclude something else, that is a design signal, not a coverage problem.

### Where `final` and `readonly` do and do not apply

Classes are `final` everywhere. Classes are `readonly` everywhere **except
NativePHP components**, which hold the state the renderer reads on re-render and
cannot be immutable.

That exemption is named explicitly in the architecture test rather than left as a
gap. If you add a class that needs to be mutable and is not a component, the test
will refuse it and it is probably the wrong design.

## Committing

- `git commit -s` — DCO sign-off, enforced.
- A `Spec:` trailer naming a real requirement that exists on `spec@main`, e.g.
  `Spec: N2-R4`. Enforced by the shared `spec-check` workflow, which reads the
  trailer from **both** the commit and the pull request body.
- Conventional commits — enforced by commitlint.
- **No AI attribution anywhere.** Not in commits, not in pull request bodies, not
  in squash messages.
- Stage paths explicitly. Never `git add -A`.

## Versioning

**There is none yet, deliberately.** This repo tracks `main` and takes no
releases while it catches up with what the main repositories have already
shipped. It locks no goal on the release train, so nothing there waits on it. It
is pinned with the rest once it has caught up.

That means: push to `main` through pull requests as usual, and do not tag.

## Building for a device

Builds and signing run in **Bifrost**, NativePHP's cloud service. **No signing
material lives in this repository** and none should ever be committed. Every
store build records the commit it came from and the Bifrost run that produced it,
so an operator can check what they installed against what is here.

Local development is `php artisan native:run`.
