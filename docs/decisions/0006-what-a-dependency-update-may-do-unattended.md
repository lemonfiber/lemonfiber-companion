# 0006: What a dependency update may do unattended

**Status:** Accepted · **Date:** 2026-09-11

## Context

Dependabot updates this repository's dependencies
([ADR-0016](https://github.com/lemonfiber/spec/blob/main/00-overview/decisions/0016-dependabot-over-renovate.md)
in the spec settles which tool). This repository is an awkward shape for it:
thirteen manifests, twelve of them path modules resolved from inside the repo,
and a dependency set where several packages *are the gates*.

That last part is the reason this needs stating. Most dependency updates change
what the code can do. A few change **what the gates catch** — a new PHPStan rule
version, a new ergebnis or shipmonk rule set, a new Pest or Rector release. An
update that turns a rule on is indistinguishable, in a green CI run, from one
that turns a rule off.

## Decision

**Patch and minor updates auto-merge when every gate is green. Anything that
could change what a gate catches is read by a person, whatever its version
number.**

Concretely, a human reads it when the update is:

- any major version;
- any analyser, rule set, or test framework — PHPStan and its extensions,
  larastan, ergebnis, shipmonk, spaze, cognitive-complexity, Pest, Rector, Pint;
- `nativephp/mobile`, because it owns the EDGE vocabulary the template tests
  read from it, so an update can change what a template is allowed to say;
- `lemonfiber/sdk-php`, because the contract is the thing this app is not
  allowed to reach past.

Everything else, on green, merges itself.

Two rules about the modules:

- **A module manifest pins nothing the root does not.** Twelve manifests each
  free to pin independently is twelve places for a conflict to hide.
- **Intra-module constraints are not Dependabot's business.** They point at path
  packages resolved from this repository, and a bot bumping them would be
  bumping a version that does not come from anywhere.

## Cost

The exempt list is maintained by hand and will go stale. It is written here
rather than only in `dependabot.yml` so that when it does, the reasoning is
available to whoever notices.

## Wrong if

The volume of held updates becomes large enough that they queue rather than get
read — at which point holding them is theatre, and the answer is fewer
dependencies rather than a longer exempt list.
