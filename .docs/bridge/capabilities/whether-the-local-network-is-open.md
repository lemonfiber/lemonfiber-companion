# Whether the local network is open

One question: does the platform refuse this app the way to one address on the
local network.

Serves `N4-R17`, `N1-R10`.

## Why it is asked

iOS asks the operator before an app may reach the local network. A refused
permission and a switched-off machine both reach the socket as silence, and
their remedies are opposite: one is a switch in Settings, the other is the
machine. The platform knows which, and this call asks it.

It is asked only after a reach met silence on a phone that has a network, so a
stack that answers never costs the question.

## What it answers

`Lemonfiber.LocalNetwork.Probe`, with `host` and `port`:

| outcome | means |
|---|---|
| `forbidden` | the platform refuses this app the way to that address |
| `open` | nothing about this app is refused on the way |

**Only `forbidden` is load-bearing.** Any other word, a malformed envelope, or
no bridge at all reads as `open`, so the app reports what it reports without
this call: the stack did not answer. Reading silence as a refusal would send
every run without a handset to a Settings switch that does not exist there.

## How each platform answers

**iOS.** A connection to the address is started and watched for one second.
A path left unsatisfied with `localNetworkDenied` as its reason is `forbidden`;
anything else is `open`. `LocalNetworkRule` decides, and `swift test` holds it.

**Android.** No local-network permission exists, so the answer is always
`open`. `LocalNetworkRule` says so, and its Kotlin test holds it.

## Nothing secret

The shim logs the outcome word. It does not log the host or the port.

## Watched

Not yet on a device. Both rules pass their tests; the iOS connection and the
one-second wait have not run on an iPhone.
