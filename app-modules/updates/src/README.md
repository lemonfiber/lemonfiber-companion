# Updates

What the app decides about an update that the stack has not already decided for
it — and nothing else. Two decisions, both about a screen:

| | |
|---|---|
| `NotArrivedFirst` | what became of each service, with what needs attention first (`N2-R18`) |
| `WorthNoticing` | the releases somebody in the house would see the difference from (`N2-R16`) |

## What is deliberately not here

**Which of the three failures is worst.** *Not fetched*, *not started* and *not
reached* are a network, a service and a machine. `NotArrivedFirst` puts all
three above what arrived and ranks none of them against the others, because
grading them would be this app forming an opinion about a situation it cannot
see. They stay told apart on the row, which is where an operator reads what to
do about one.

**Whether the stack is current, pending or stale.** That is the stack's answer
and `N2-R15` forbids deriving it here. The app holds no opinion about which of
two version strings is later, and one that formed one would be wrong about a
withdrawn release, a patch series, and a stack whose channel the operator
changed — wrong silently, because nothing on either side would compare its
opinion to the stack's.

**Refusing a withdrawn release.** `Releases::worthOffering()` does that before
anything reaches here. A second place one could be let through is a second place
to get `N2-R16` wrong.

**Applying one.** `KeepingCurrent` is the port and `Upkeepers` is the adapter;
this module may not name the SDK (`N1-R16`) and has nothing to say about a wire.
