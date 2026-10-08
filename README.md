# lemonfiber companion

A phone app for a [lemonfiber](https://github.com/lemonfiber/lemonfiber) stack.
It lets you check on and fix your media stack, or ask for something to watch,
from your phone instead of from the machine the stack runs on.

It is a native app on iOS and Android, built with
[NativePHP](https://nativephp.com): Blade templates compile to a native element
tree, which the platform draws as SwiftUI or Jetpack Compose. There is no web
view.

> **Status:** in development. There is no release and no app-store build yet;
> to try it you build it yourself.

## What it does

Two people use a stack, and the credential they sign in with decides which app
they get.

**An operator** sees a verdict first — whether the stack is healthy — then its
findings worst-first, and can act on them: repair, update, snapshot, undo.

**A household member** sees what is available to them and can ask for something.
They are not shown the machinery, because it is not theirs to operate.

## What it will not do

- **Talk to the API itself.** Every call goes through
  [`sdk-php`](https://github.com/lemonfiber/sdk-php). Where the SDK lacks
  something, the SDK is changed first.
- **Send anything anywhere.** No analytics, no crash reporting, no telemetry.
  There is no setting for this because there is no code for it.
- **Set up a stack.** First-run setup happens at the machine. The app says so
  rather than omitting it silently.
- **Accept any certificate.** The certificate fingerprint comes from the pairing
  material and is pinned; a changed certificate is refused, not warned about.

## Working on it

You need PHP 8.5 and Composer.

`composer install` needs a credential for `plugins.nativephp.com`: the app uses
a paid NativePHP plugin, and without the credential the download fails with HTTP
`402`. Put it in Composer's `auth.json` or the `COMPOSER_AUTH` environment
variable, as Composer's
[authentication guide](https://getcomposer.org/doc/articles/authentication-for-private-packages.md)
describes.

```bash
composer install
composer ci          # every PHP gate, in the order CI runs them
composer test        # just the test suite
composer lint:fix    # formatting
```

The code is split into modules under `app-modules/`. Each module declares what
kind it is, and that declaration generates the rules about what it may depend
on, so a new module is governed from the moment it exists.

[`ARCHITECTURE.md`](ARCHITECTURE.md) describes the modules and indexes the
rules in [`.docs/architecture/`](.docs/architecture/), each with the check that
enforces it; a test fails if a rule claims a check it does not have.
[`.docs/decisions/`](.docs/decisions/) records why the code is shaped this way;
product decisions live in the
[specification](https://github.com/lemonfiber/spec). Read the
[contributing guide](https://github.com/lemonfiber/spec/blob/main/50-governance/contributing.md)
and [`AGENTS.md`](AGENTS.md) before your first pull request.

Report a vulnerability privately, as
[SECURITY.md](https://github.com/lemonfiber/.github/blob/main/SECURITY.md)
describes.

## Licence

[Hippocratic License 3.0](LICENSE).

lemonfiber is made by [NightWorksIO](https://nightworks.io).
