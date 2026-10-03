# The way around a stack

What every screen about a stack draws in both surfaces: the stack's name in the
top bar, the list of stacks that name opens, and the menu beside it.

A surface may never name another surface, and both the operator's screens and a
member's screens carry this. So it is a kind of its own, `wayfinding`: it may
reach the kernel, the design module and the capabilities, and the surfaces may
reach it.

## What it publishes

| | |
|---|---|
| `Screens\FindsItsWayAroundAStack` | The trait a screen about a stack uses. It carries the list of stacks and hands NativePHP the menu. A surface wraps it in its own trait, which says whether a screen opens on top of another. |
| `Screens\ChoosesAStack` | The list of stacks as a sheet over the screen, listening to each stack while it is open. |
| `TheWayAround` | What a screen reads to find its way: the stack its route names, the stacks to choose from, where choosing one leads, where the app opens, and whose menu it draws. |
| `WhoTheMenuIsFor` | Whose session this phone holds for the stack: nobody, a member, or the operator. |
| `TheTabs` | The operator's four tabs and the screen each opens. Which screen class draws each tab is the operator surface's own. |
| `AScreenWithoutAStack` | Every screen that is not about one stack, by path. |

## Whose menu it is

| | |
|---|---|
| `N28-R1` | Every screen about a stack has the stack's name in the top bar and a menu control. |
| `N28-R2` | The stack's name opens the list of stacks. |
| `N3-R9` | A member is never shown lifecycle controls, logs, credentials or diagnostics. |

The menu follows whose session this phone holds for the stack, read through
`WhereTappingLeads`, the same answer the list of stacks reads to decide where a
tap leads:

- **Nobody:** the list of stacks, Stack settings, and the phone's settings.
- **A member:** those, with what they can ask for and what they can watch.
- **The operator:** those, with what is new and every group of the stack's
  own screens.

Stack settings is in every menu because it is what this phone keeps of the
stack, and the only way to take a stack off the phone, a stack that refuses to
let somebody in included.

`tests/Arch/EveryScreenAboutAStackHasTheMenuTest.php` holds every operator and
household screen that answers which stack it is about to this trait.
