# lemonfiber companion

A companion app for a [lemonfiber](https://github.com/lemonfiber/lemonfiber)
stack, for phones. It is the fourth surface after the CLI, the TUI and the web
UI — and the first one that does not run on the machine it operates.

It renders natively. There is no web view, no DOM and no JavaScript bridge:
Blade templates compile to a native element tree, which the platform draws as
SwiftUI or Jetpack Compose.

> **Status: early.** This repository is catching up with the rest of the
> estate. Nothing is versioned yet and nothing is published; everything lands on
> `main` until it is complete enough to pin alongside the other repositories.

## What it does

Two people use a stack, and the credential they sign in with decides which app
they get.

**An operator** sees a verdict first — whether the stack is healthy — then its
findings worst-first, and can act on them: repair, update, snapshot, undo.

**A household member** sees what is available to them and can ask for something.
They are not shown the machinery, because it is not theirs to operate.

## What it will not do

- **Talk to the API itself.** Every call goes through the published SDK. Where
  the SDK lacks something, the gap is raised against the SDK and the work stops
  rather than reaching past it.
- **Send anything anywhere.** No analytics, no crash reporting, no telemetry.
  There is no setting for this because there is no code for it.
- **Set up a stack.** First-run setup happens at the machine. The app says so
  rather than omitting it silently.
- **Accept any certificate.** The certificate fingerprint comes from the pairing
  material and is pinned; a changed certificate is refused, not warned about.

## Layout

```
bootstrap/Composition/  the composition root, and nothing else
app-modules/
  kernel/               ports, values, outcomes — depends on nothing
  design/               EDGE components and theme tokens

  connection/           pairing, the session, holding more than one stack
  stacks/  health/  backups/  updates/

  operator/             navigation and screen composition
  household/            navigation and screen composition

  sdk/                  the only module that calls the SDK
  device/  vault/       the platform, and secure storage

  dx/                   stand-ins for a stack, installed only under require-dev
```

Each module declares what kind it is, and that declaration generates the rules
about what it may depend on — so a module added later is governed the moment it
exists rather than when somebody remembers to write its test.

## Working on it

```bash
composer install
composer ci          # every gate, in the order CI runs them
composer test        # just the suite
composer lint:fix    # formatting
```

[`ARCHITECTURE.md`](ARCHITECTURE.md) is the contract — the rules, and for each
one the mechanism that enforces it. A test reads that table and fails if a rule
claims an enforcement it does not have, so it cannot quietly go out of date.

[`AGENTS.md`](AGENTS.md) is the guide for anyone, human or otherwise, making a
change here. [`.docs/decisions/`](.docs/decisions/) records why this codebase is
shaped the way it is; decisions about the product live in the
[spec](https://github.com/lemonfiber/spec).

## Licence

[Hippocratic License 3.0](LICENSE).
