# The bridge

`lemonfiber/bridge` is this application's own half of the phone: the Kotlin and
the Swift that answer the calls PHP makes when it needs something only the
device can do.

The whole column is ours — the name on the wire, the shape that comes back, the
Kotlin, the Swift, and the tests over all four. A capability whose answers were
chosen elsewhere is a capability whose answers the app has to guess behind.

## How to read this directory

| Page | What it covers |
|---|---|
| [the-shape-of-a-capability.md](the-shape-of-a-capability.md) | The pattern every capability follows, and what "done" means for one |
| [the-wire.md](the-wire.md) | Names, envelopes, how a function is declared and how it is found |
| [android.md](android.md) | Kotlin: layout, the harness, what cannot be tested off a device |
| [ios.md](ios.md) | Swift: layout, the harness, the same question answered differently |
| [capabilities/](capabilities/) | One page per capability: what it answers, and the states it keeps apart |

The requirements each capability serves are named on its page and live in
[`.docs/requirements/`](../requirements/). The spec is canonical; where a page
here and a requirement disagree, the requirement is right and the page is a
defect.

## What is ours and what is not

Ours: the fourteen capability functions — telling somebody, keeping a secret,
reading a code, whether there is a link, handing something over — and the five
for capture protection and the app lock.

Not ours, deliberately: `nativephp/mobile`, which is the runtime, the element
collector and the Blade precompiler; and `nativephp/mobile-ui`, which is some
forty element renderers. Those are a framework. The line is drawn at *what the
app asks the device for*, which is the surface whose answers this application
reasons about.

## The licence is not the repository's

The root of this repository is Hippocratic 3.0. `bridge/` is not: it carries its
own [LICENSE](../../bridge/LICENSE), which permits no use of any kind by anybody
else. It is readable here because everything in this repository is readable
here, and that is all.

Anyone who wants these capabilities for their own application should buy them
from the [NativePHP plugin marketplace](https://nativephp.com/plugins), which is
how the framework this application is built on gets paid for.
