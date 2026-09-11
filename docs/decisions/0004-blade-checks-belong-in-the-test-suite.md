# 0004: The Blade checks are tests, not a script

**Status:** Accepted · **Date:** 2026-09-11

## Context

EDGE styling looks like Tailwind and is not. There is no stylesheet, no build
and no browser: an unrecognised utility class is parsed, found to mean nothing,
and dropped. The screen renders, looks wrong, and says nothing about why.

PHPStan reads PHP. Pest's architecture rules read classes. Neither opens a
`.blade.php` file, so this needed something else — and the first attempt was a
standalone `scripts/guards.php` that bootstrapped the framework itself.

## Decision

They are Pest tests in `tests/Templates`.

The gap is real — no analyser reads a template — but it does not follow that it
needs its own runner. As tests they share one report format, one CI job and one
already-booted application, which the script was bootstrapping by hand for the
sole purpose of asking the framework a question.

Two things follow from being inside the suite. The checks can use the container,
which the EDGE check needs: it drives `TailwindParser` and listens for the
warning the parser already emits. And they are subject to the same rules as
every other test, rather than being a second body of code with its own
conventions.

## Related

The vocabulary is never copied. The check asks the installed parser what it
supports, so it tracks the package exactly; a list maintained here would
disagree with the renderer the first time a utility was added, and disagree
silently.

## Cost

They run with the suite rather than with the fast gates, so a template mistake
is found seconds later than it might be. The fast job runs Arch and Templates
first to narrow that.

## Wrong if

The checks grow slow enough to matter, or the framework starts reporting
unsupported classes itself — at which point this becomes a thin wrapper over its
diagnostics rather than a check of its own.
