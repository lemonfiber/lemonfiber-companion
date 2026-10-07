# The SuperNative surface

Part of [the rules](../../ARCHITECTURE.md#the-rules) `ARCHITECTURE.md` indexes.

| | Rule | Enforced by |
|---|---|---|
| F1 | Components are thin: hold state, delegate decisions | phpstan: `cognitive_complexity` + `H3`'s method cap |
| F2 | Presenters are pure: data in, view model out, no ports injected | arch: no interface in a presenter's constructor, over a set the same rule asserts it found |
| F3 | Blade holds no logic; theme tokens only; every EDGE class and tag verified | `tests/Templates`, against the installed parser and registries |
| F5 | Every interactive element announces itself to a screen reader | `tests/Templates` |
| F6 | Every list has an empty state | `tests/Templates` |
| F7 | No template reads a value that has one destination — a session, a credential, a stack address | `tests/Templates`, from the same table F2's surface rule counts against |
| F4 | A screen that takes a port carries `#[Lazy]`; one whose content changes while open carries `#[Poll]` | arch for the first; review for the second |
| F8 | A screen shows findings in the order a capability decided, never the order they arrived | arch: a screen that names findings names `WorstFirst` |
| F9 | A class list is written out, never decided at runtime | arch: over the text of every template |
| F10 | Every method a template calls is one its screen has, and every screen that renders is paired | `tests/Templates` |
| F11 | Every component a screen uses is classified as a control or as furniture, so F5 cannot pass over one nobody thought about | `tests/Templates` |
| F12 | Every screen can be reached from the one the app opens on, by following navigation from screen to screen | `tests/Feature`: the walk from the screen the router serves at launch |
| F13 | A component draws its slot on every branch it has — Blade renders a slot before the component, so one behind an `@if` reaches the device anyway | arch |
| F14 | Every step a template takes after its screen answered, and every step a component takes off what it was handed — a field or a further call — is one that value has | `tests/Templates`: the chain walked by declared type, from the screen's return type or the component's property onward |
| F15 | Every screen the router serves is built the way the app builds it, drawn, and draws something — the render path is where a frame is actually decided | `tests/Feature`: every route's screen, rendered with stand-ins answering |
| F16 | The column a screen's content sits in is written once, in the `content` component, whose slot is drawn inside it | arch |
| F17 | Every operator screen about a stack carries the side menu and the list of stacks, and every member screen about a stack carries the member's four tabs and no menu; one that goes without is named with why | arch: every operator screen answering which stack it is about uses the wayfinding trait and carries the list of stacks; every household screen answering it uses the household's own trait and has neither the menu nor the list; both against a register that may shrink and may not grow |
| F18 | Every screen of one stack is an operator's tab, a menu item, a member's tab, or a step of another screen named with where it begins | arch: read from the screens a stack has, against the menu, both surfaces' tabs and a register that may shrink and may not grow |
| F19 | Every text element takes its colour from a theme role, so it reads in the member's theme and the operator's alike | arch: over every element of every template |
| F20 | Every text element names the bundled face it is set in, with the weight class that face is, and no template picks a platform face | arch: over every element of every template, against `Typeface` |

**Why F12 is a rule of its own, given the three beside it.** Three rules already
ask about reachability and every one of them asks it of a single screen: each
screen offers a way off it, each screen under a machine offers a way back to it,
and each destination the app can describe is one some template navigates to. All
three are local, and a cluster of screens wired to each other satisfies every one
of them while sitting outside the application entirely — each has a way off, each
has something pointing at it, and nobody can get to any of them from where a
person actually starts. That is what a feature branch looks like halfway through,
and the first report would be an operator who cannot find the screen.

So F12 starts at the screen the router serves under the path a launch asks for —
derived, never named, because a rule that started at a screen somebody wrote down
would go on passing from a screen nobody sees — and follows every `@navigate`
transitively. A `@navigate` names an accessor rather than a path, so following one
means resolving the accessor to the case it hands out and the case to the screen
registered under it; an accessor this cannot resolve fails the rule by name,
because a silently dropped edge makes a stranded screen look reachable.

**Why F9 exists, given F3.** Every rule about a class list is handed the answer
of one function, `Template::classStrings()`, and that function drops any token
holding a runtime expression rather than guessing at it. It has to: with the
echo deleted, `bg-{{ $tone }}` is `bg-`, an unknown utility nobody wrote. But
what gets dropped from `class="{{ $open ? 'bg-theme-accent' : '' }}"` is the
whole attribute, and the class names inside it are then read by nothing at all.

Three rules go quiet together, because all three read that one answer. F3 stops
seeing an unknown utility, DES-R33 stops seeing a literal colour, and DES-R15
stops seeing the accent set as text. A template whose ternaries hold
`bg-theme-accnt`, `bg-red-500` and `text-theme-accent` passes every rule in
`tests/Templates` — and EDGE agrees, because it parses each of those in turn,
finds it means nothing and drops it. No error, no warning, no failed build: the
screen renders wrong on a device and says nothing about why. That is the exact
failure the vocabulary check exists to catch, so a hole in it is a rule rather
than a note.

The cure is to draw a state that changes rather than to colour it.
`how-this-stack-is.blade.php` puts the selected bar in an element of its own
under an `@if`, with a static class every check can read. The two forms that
never reach `classStrings()` at all are refused alongside — a bound `:class`,
whose value is PHP rather than a class list, and `@class([...])`, which is not
an attribute for the expression to match — because a class name hidden in either
is hidden the same way and costs the same thing.

**Why F8 is a rule rather than a note on the screen.** `WorstFirst` is the one
decision `health` makes about a report, and for two commits nothing called it. A
query nothing calls passes its own test forever — so the sorter was green, the
first findings screen rendered the envelope, and `N2-R2` was held by a test
rather than by anything an operator could see. What that produces is a list that
looks ordered: the rows are right, the words are right, and on any report whose
worst finding happened to run first the order is right as well. There is no
wrong pixel, no exception and no log line; the next report is simply in the
wrong order on somebody's phone, with a full disk above a leaking tunnel. So the
gate is on the screen rather than on the query — a screen under `Internal/Screens`
that names findings at all names `WorstFirst` too. Read over the text, because
what has to be caught is a screen that names it nowhere, and an absence has no
call site to follow to.

EDGE styling is **Tailwind-shaped and is not Tailwind**. There is no CSS build,
no JIT and no stylesheet to come up short. An unrecognised class is parsed,
found to mean nothing, and dropped — the screen renders, looks wrong, and says
nothing about why. `tests/Templates` drives the framework's own parser over every
template and fails on what it reports, rather than keeping a second copy of the
supported vocabulary that would silently drift from the installed package.

**Nothing in this suite writes the vocabulary down.** Unknown classes come from
`TailwindParser`'s own diagnostic channel, which already knows that a platform
variant aimed at the other platform is a deliberate no-op rather than a mistake.
Literal colours come from `TailwindParser::resolveColorValue`, which answers for
`red-500` and `#B91C1C` and stays silent for `theme-background` and `2xl` —
which is exactly the distinction DES-R33 turns on. Tag names come from the
element registry, the component registry, and the types the collector renders
itself. A transcribed list would keep passing through a NativePHP release that
changed any of them, which is how a rule dies without anyone noticing.

**A `bg-theme-*` token reported as unknown is a true answer, not a false
positive.** The theme resolver is provided by the application rather than by the
package. `Modules\Design\Api\Theme` builds that resolver for one of the two
themes, and the composition root hands it over for each screen, so the roles
this surface asserts resolve and every other token is still reported — which
is the answer rather than a gap. A screen drawn for the operator's session is
painted in the operator's theme and every other screen in the member's, both
on the brand's ink theme whatever the phone is set to, and no setting chooses
between them (DES-R28, DES-R29, DES-R30). App settings and What's new, which
belong to nobody's session, keep the theme of the screen they open over.

**Nothing about the palette is typed twice without being checked.** The
hexes live in the `ThemeToken` enum because a module may not read a file (B3)
and a provider may not read one at boot (A9); `tests/Arch/BrandPaletteParityTest`
checks them against `app-modules/design/resources/tokens.json`, which the
hygiene gate in turn checks by digest against `brand:tokens/tokens.json`. The
brand repository makes the same arrangement for its own `tokens.css`.
