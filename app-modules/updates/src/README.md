# Updates

What the app decides about an update that the stack has not already decided for
it — and nothing else. One decision, about a screen:

| | |
|---|---|
| `NotArrivedFirst` | what became of each service, with what needs attention first (`N2-R18`) |

## What is deliberately not here

**Which of the three failures is worst.** *Not fetched*, *not started* and *not
reached* are a network, a service and a machine. `NotArrivedFirst` puts all
three above what arrived and ranks none of them against the others, because
grading them would be this app forming an opinion about a situation it cannot
see. They stay told apart on the row, which is where an operator reads what to
do about one.

**Whether an update is waiting.** That is the stack's answer, the `update`
envelope's top-level `state`, and `N2-R15` forbids deriving it here. The app
holds no opinion about which of two version strings is later.

**Which release to take.** There is no such choice. The stack moves each
service onto the version its own build pins, and the release history is where
those pins came from rather than a list of offers.

**Applying one.** `KeepingCurrent` is the port and `Upkeepers` is the adapter;
this module may not name the SDK (`N1-R16`) and has nothing to say about a wire.
