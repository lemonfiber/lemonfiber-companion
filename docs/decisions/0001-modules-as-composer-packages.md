# 0001: Modules are composer packages, so the resolver enforces the boundaries

**Status:** Accepted · **Date:** 2026-09-11

## Context

The app needed internal boundaries. Two Laravel modules packages were
candidates, and the popular answer was not the right one.

`nwidart/laravel-modules` is the better known of the two. It generates a full
mini-Laravel per module — config, routes, migrations, resources, tests — and
tracks which modules are enabled in a `modules_statuses.json` read at runtime.

`internachi/modular` gives each module a real `composer.json` and loads it
through ordinary PSR-4 from a composer path repository.

## Decision

`internachi/modular`.

The deciding property is not the smaller scaffold, though that matters in a repo
with no HTTP routes, no SQL and a stated dislike of generated files nobody
reads. It is that **a module's dependencies become composer dependencies**.

`modules/sdk` is the only manifest requiring `lemonfiber/sdk-php`. So a surface
module that types `Lemonfiber\Sdk` is a shadow dependency, and
`composer-dependency-analyser` fails on it. The SDK-only rule — the one the
product spec states as `N1-R16` — stops being a convention a reviewer applies
and becomes one the dependency resolver applies.

`modules_statuses.json` was the other half of the decision. It is runtime-mutable
global state, which this codebase refuses everywhere else, and it can drift
between a laptop and a device.

## Cost

Each module carries a manifest to maintain, and those manifests are load-bearing
— `extra.lemonfiber.kind` generates the module's architecture rules. Nothing
validated them at first, and a bogus licence planted in one passed the root
`composer validate --strict` without comment. `composer validate:modules` now
validates each one.

## Wrong if

Composer path repositories stop resolving cleanly at this scale, or the analyser
can no longer distinguish a module's declared dependencies from the root's.
