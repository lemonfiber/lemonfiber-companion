# Keeping a secret

The platform's own secure store — Keychain on iOS, the encrypted store on
Android. Every session this app holds and every stack it is paired with lives
here, and nowhere else.

Serves `N4-R5`, `N4-R6`, `N1-R7`, `N1-R11`, `N1-R15`, `N1-R32`, `N1-R33`.

## Why a write answers a word rather than a bool

`WhySessionCannotBeKept` and `WhyAStackCannotBeRemembered` each name two cases,
`DeviceHasNoSecureStorage` and `StoreWouldNotOpen`, and each reaches the
operator as a different screen with a different remedy. One is *this phone
cannot do this*; the other is *try again, and if it keeps happening something is
wrong with the device*.

A boolean cannot carry that, so the adapter behind one has to infer it.

## What it answers

`Lemonfiber.Storage.Keep`:

| outcome | `because` | means |
|---|---|---|
| `kept` | — | written |
| `refused` | `no_store_on_this_device` | no secure store exists here; nothing will fix it |
| `refused` | `store_would_not_open` | it exists and would not open; trying again is reasonable |

`Lemonfiber.Storage.Read`:

| outcome | `because` | means |
|---|---|---|
| `found` | — | the value is in `value` |
| `nothing` | — | no such key; the ordinary case on a first launch |
| `refused` | `no_store_on_this_device` / `store_would_not_open` | as above |

The distinction between `nothing` and `refused` is the one that matters most
here and the one a bool cannot carry: *this device is not paired* and *this
device cannot be asked whether it is paired* look identical to a caller that
only knows the read came back empty, and they are opposite answers. A launch
that read a refusal as "no stacks configured" would silently offer to pair a
machine that is already paired.

`Lemonfiber.Storage.Forget` answers `forgotten` or a refusal, and forgetting a
key that was never kept is `forgotten` rather than an error — it is the ordinary
case after a refused write, and the one thing that must always work is getting
rid of a session.

## Accessibility is part of the contract

iOS makes you choose when an item may be decrypted; Android has no equivalent
knob. It belongs in the wire contract rather than in the iOS shim, so that both
platforms answer the same question and the Android side answers it explicitly
rather than by omission. Re-keeping an existing key with a different
accessibility migrates it in place.

## Nothing secret

The hardest case, and the shortest rule: **no key, no value, and no stack
identifier reaches a log line, a breadcrumb or a cache file.** The shim logs the
outcome word and the reason word — both closed sets, neither derived from
anything the caller passed — and nothing else.

An exception raised by the platform is caught and converted to a reason rather
than propagated, because a platform exception message can carry the key it
failed on, and an uncaught one reaches a crash reporter.
