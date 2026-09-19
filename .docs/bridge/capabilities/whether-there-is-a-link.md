# Whether there is a link

One question: can this device reach anything at all right now.

Serves `N1-R10`, `N4-R12`.

## Deliberately narrow

The platform can report whether the link is wifi, cellular or ethernet, whether
it is metered, and whether Low Data Mode is on. None of it is read here.
`N4-R12` keeps this app from reporting anything about the operator's device, and
a value held but not sent is one commit away from being sent.

The shim does not read those fields and the envelope has no place to put them,
so adding one means explaining why rather than uncommenting a line.

## What it answers

`Lemonfiber.Link.Status`:

| outcome | means |
|---|---|
| `reachable` | something is reachable from here |
| `unreachable` | nothing is |

That is the entire contract. The one distinction worth having is between *this
phone has no network* and *that machine is not answering*, which are two
sentences with two different remedies — and the app cannot draw it without this
call, which is why a missing handler mattered.

## Why not just try the request and see

Because the two failures look the same from a socket and read differently to a
person. A stack that is off answers nothing, and a phone in a lift answers
nothing, and telling somebody to go and check their machine when they are in a
lift is the kind of wrong that makes an app feel stupid.

## Nothing secret

Nothing here is secret, and nothing here is *about* the device either, which is
the stricter rule this capability keeps. The shim logs the outcome word. It does
not log an SSID, an interface name, a carrier, or an address.

## Watched

**Android — SM-A515F (`R58N12ZSQ3H`), debug build.** Both answers, on the same
device, twenty seconds apart:

```
nativephp_call() called with method: Lemonfiber.Link.Status
Lemonfiber: link: reachable
```

```
Lemonfiber: link: unreachable
```

The first with the phone on wifi; the second with airplane mode on and nothing
else changed. Every line the shim wrote is the outcome word — no SSID, no
interface name, no carrier, no address.

**It has to be asked before it can be watched.** `Opening` asks this *inside*
the loop over configured stacks, deliberately: an unpaired device has nowhere to
send anything, so a first run never puts the question at all. A handset with no
stacks paired therefore never reaches this call, and a build watched that way
proves nothing about it. The watch above is over a build with `DX_STANDS_IN=1`,
which pairs three stands-in stacks at launch and makes the question real.

**iOS is unproven.** No iPhone has been attached to this work. `LinkRule` passes
its tests under `swift test`; `LinkFunctions.swift` has never run on a device,
and the `NWPathMonitor` snapshot it takes — start, wait, cancel — is the part
that most wants watching.
