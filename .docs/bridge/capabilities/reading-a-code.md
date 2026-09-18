# Reading a code

The camera, opened once, to read the pairing code a stack is showing.

Serves `N4-R2`, `N4-R3`, `N1-R54`.

## Why the outcome is a closed set

A scan can end four ways and each one is a different sentence on the screen. A
boundary carrying them as free strings puts a `default` arm behind a spelling:
a misspelt refusal falls through to *the operator pressed back*, and that screen
offers to try again forever without mentioning the typed road.

The set is closed on both sides of the wire, so a wrong word is a compile error.

## What it answers

`Lemonfiber.Scanning.Read`:

| outcome | `because` | means |
|---|---|---|
| `read` | — | a code was read; the payload rides on the event, never in this result |
| `nothing` | `the_operator_closed_it` | they backed out; the screen offers the typed road |
| `nothing` | `the_camera_is_not_permitted` | refused; the screen says so and offers the typed road |
| `nothing` | `there_is_no_camera` | no camera on this device at all |

**One distinction the vendor cannot make and ours must.** A camera refused *in
the dialog just now* and a camera refused *in settings some time ago* arrive
identically, and they need different sentences: the first can be asked again at
the point of first use, the second cannot and has to send the operator to
settings. The rule takes the same shape as the notification one — whether the
platform would still explain, and whether this application has ever asked — and
answers `the_camera_is_not_permitted` either way, with a second field saying
whether asking again is possible.

## Nothing secret

**The scanned payload is pairing material.** It never appears in a log line, at
any level, in either shim — not truncated, not hashed, not "first eight
characters". The result envelope carries `outcome` and nothing else; the payload
reaches PHP through the event, which is read once and dropped.

This is the one capability where a debug log left in by accident would be a
disclosure rather than an annoyance, so the test that reads the source for
logged values is not optional here.
