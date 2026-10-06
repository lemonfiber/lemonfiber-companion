# The way around a stack

How each surface finds its way around a stack. An operator's screen draws the
stack's name in the top bar, the list of stacks that name opens, and the menu
beside it, with four tabs: Health, Services, Updates and Repairs. A member's
screen draws four tabs of its own, Home, Search, Requests and Profile, and no
menu.

A surface may never name another surface, and both surfaces read their tabs,
where choosing a stack leads and whose session the phone holds from here. So
it is a kind of its own, `wayfinding`: it may reach the kernel, the design
module and the capabilities, and the surfaces may reach it.

## What it publishes

| | |
|---|---|
| `Screens\FindsItsWayAroundAStack` | The trait an operator's screen about a stack uses. It carries the list of stacks and hands NativePHP the menu. The operator's own trait wraps it, and says whether a screen opens on top of another and how much is new. |
| `Screens\ChoosesAStack` | The list of stacks as a sheet over the screen, listening to each stack while it is open. |
| `Screens\DrawsItsTemplate` | The `render()` of a screen that hands its template nothing. The screen names the template in its `TEMPLATE` constant. |
| `Screens\WaitsAFrameForWhatTheStackServes` | The frame every screen that asks a stack anything draws where asking the stack what it serves was the frame's one reading: the platform's indicator and the next frame at once. Its first frame is where it opens, and opening after a break asks every stack again. Both stack traits carry it. |
| `Screens\AsksTheStackAgain` | The `askAgain()` the operator taps, which asks the stack again what it offers as well as reading again. |
| `Screens\AsksAgain` | The `again()` of a screen whose answer is its `$answered`: letting go of the answer, so the next frame asks. |
| `TheWayAround` | What a screen reads to find its way: the stack its route names, the stacks to choose from, where choosing one leads, where the app opens, and whose menu it draws. |
| `WhoTheMenuIsFor` | Whose session this phone holds for the stack: nobody, a member, or the operator. |
| `TheTabs` | The operator's four tabs and the screen each opens. Which screen class draws each tab is the operator surface's own. |
| `TheHouseholdsTabs` | A member's four tabs and the screen each opens. Which screen class draws each tab is the household surface's own. |
| `WhoTheSettingsSpeakTo` | Whose words App settings speaks in: anyone's, or a member's when their Profile opens it. |
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

- **Nobody, or a member:** the list of stacks, Stack settings, and the
  phone's settings. A member's own screens draw no menu, and the operator's
  are never built for a member's session.
- **The operator:** those, with what is new and every group of the stack's
  own screens.

Stack settings is in every menu because it is what this phone keeps of the
stack, and the operator's way to take a stack off the phone, a stack that
refuses to let somebody in included. A member takes a house off the phone on
Profile.

`tests/Arch/EveryScreenAboutAStackHasTheMenuTest.php` holds every operator
screen that answers which stack it is about to this trait, and every household
screen to the household's own, with no menu.
