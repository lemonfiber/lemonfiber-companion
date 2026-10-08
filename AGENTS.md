# Working in `lemonfiber-companion`

> **Start at the roadmap and board on [lemonfiber.app](https://lemonfiber.app),
> rendered from the report of where every unreleased version stands. Then the
> rules** every repository shares:
> [working in the repositories](https://github.com/lemonfiber/spec/blob/main/50-governance/working-in-the-repositories.md)
> and [the rules for agents](https://github.com/lemonfiber/spec/blob/main/50-governance/ai-contributors.md).
> This file holds only what is true of this repository.

## What this repo is

The mobile companion app, a fourth surface for lemonfiber, built with NativePHP in
SuperNative mode. Spec: [area N](https://github.com/lemonfiber/spec/tree/main/10-functional/features/n-companion)
and [ADR-0017](https://github.com/lemonfiber/spec/blob/main/00-overview/decisions/0017-the-companion-app-as-a-fourth-surface.md).

It renders the core's answers and decides nothing the core does not (`G1-R2`). One
application serves the operator and the household, and **the credential that signs
in decides which**: no setting, no second build (`N3-R1`). What a household member
may do is the core's answer; the app holds no permission model (`N3-R2`).

## The one rule that is different from everywhere else

**The SDK is the only way out, and a gap in it is a question rather than a
workaround.** Every call to lemonfiber goes through `lemonfiber/sdk-php`; the app
issues no HTTP request of its own, builds no URL, and parses no envelope the SDK
did not hand it (`N1-R16`). Where the SDK lacks something a screen needs, the work
stops there (`N1-R17`): say what is blocked and move on. Never reach past the SDK,
re-implement a call beside it, approximate from a neighbouring endpoint, or add an
HTTP client to `composer.json`. An architecture test enforces the first half.

## Where code goes

```
bootstrap/Composition/  the composition root, and nothing else
  NativePHP/            what exists only because of that one package
app-modules/
  kernel/               ports, values, outcomes — depends on nothing
  design/               EDGE components and theme tokens
  connection/ stacks/ services/ requests/ health/ backups/ updates/  capability
  operator/ household/                              surface
  sdk/ device/ vault/ codes/ seal/                  adapter
  dx/                                               stand-in
  store-kit/                                        store kit
```

| Adding | Goes in |
|---|---|
| a screen | its surface module |
| domain logic | a capability module |
| anything that talks to the outside | an adapter module |
| a shared value or a port | `kernel` |
| what a capability keeps between launches | its `src/Internal/Store` and `database/migrations`, behind a port it declares |
| a reusable component or a token | `design` |
| a rule about all of the above | `tests/Arch` |

Each module declares its kind in its `composer.json` under
`extra.lemonfiber.kind`, and that declaration generates its architecture rules:

| Kind | May use | Never |
|---|---|---|
| `kernel` | nothing | everything |
| `capability` | `kernel`; Illuminate in its store only | Illuminate outside its store, Native, the SDK, other capabilities, adapters, surfaces |
| `design` | `kernel`, `Native\Mobile` | the SDK, capabilities, surfaces |
| `surface` | `kernel`, `design`, capabilities, `Native\Mobile` | the SDK, adapters, the other surface |
| `adapter` | `kernel`, the one package it adapts | capabilities, surfaces, other adapters |
| `stand-in` | `kernel`, adapters, any outside package | capabilities, design, surfaces |

A module publishes `Modules\<Name>\Api`; everything under `Internal` is
unreachable from elsewhere. `modules/sdk` is the only shipped manifest requiring
`lemonfiber/sdk-php`; `modules/dx` is a stand-in installed under `require-dev`.

## What it must never do

- Add a web view: `<native:webview>` is forbidden (ADR-0017), by an arch test.
- Put logic in a Blade view, which reads from its component and nothing else.
- Assert a literal colour: colour comes from `bg-theme-*` / `text-theme-*` (`DES-R33`).
- Set text in a face `Typeface` does not list, or fetch a font (`DES-R32`).
- Ship analytics or third-party crash reporting (`N4-R12`); a dependency test denies the known SDKs.
- Keep a session outside Keychain or Android Keystore (`N4-R5`).
- Ask for a permission before its first use, or depend on it (`N4-R1`, `N4-R3`).

## Styling is EDGE, and EDGE is not Tailwind

The class vocabulary is Tailwind-shaped and defined by EDGE, compiled to SwiftUI
and Jetpack Compose; nothing errors on an unknown class, so `composer test:guards`
validates every class and `<native:*>` tag against the installed EDGE package.
No `tailwindcss`, `prettier-plugin-tailwindcss` or PostCSS.

## The gates

`composer ci` runs every gate CI runs over this repository's own source, in CI's
order; `composer install` turns the hooks on through `post-install-cmd`.

| Gate | Command | Bar |
|------|---------|-----|
| Format | `composer lint` | Pint, `per` preset; `composer lint:fix` writes |
| Static analysis | `composer analyse` | PHPStan `level: max`, Larastan, strict, ergebnis `allRules`, 100% type coverage, no baseline |
| Dead idioms | `composer refactor` | Rector dry-run; `composer refactor:fix` writes |
| Module manifests | `composer validate:modules` | each module's manifest, `--strict` |
| Dependencies | `composer deps` | unused and shadow dependencies, `validate --strict`, `normalize`, `audit` |
| Tests | `composer test:report` | 100% line coverage, with the Blade checks in `tests/Templates` |

`composer test:report` writes the clover that `test:floors` and Sonar read;
`test:coverage` does not. Mutation floors are declared per measured tree under
`extra.lemonfiber.floors.mutation` in the manifest nearest it, and
`scripts/mutation.php` explains each `0`; `composer test:mutation` runs it.

- `ARCHITECTURE.md` is checked: each rule in `.docs/architecture/` names its
  mechanism, and `tests/Arch/TheRulesAreRealTest.php` fails when they disagree. [`.docs/decisions/0005`](.docs/decisions/0005-how-a-rule-changes.md)
  says how a rule changes.
- Every native capability sits behind a port with a fake; the thin adapters are
  the only excluded code, listed by name in `phpunit.xml`, and a test holds the list.
- Classes are `final` and `readonly` everywhere, NativePHP components excepted
  from `readonly`, by name in the architecture test.

## Versioning and devices

The app tracks `main` and is not tagged; it locks no goal on the release train.
Store builds run in Bifrost and record the commit they came from; no signing
material lives here. Local development is `php artisan native:run`.
