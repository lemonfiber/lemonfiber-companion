# The household surface

What a member of the house sees: four tabs, Home, Search, Requests and
Profile, in a bottom bar, and no side menu.

| Tab | Screen | What it draws |
|---|---|---|
| Home | `WhatYouCanWatch` | what is theirs, then the newest title and the shelf as rows of posters, which is where a member lands |
| Search | `LookingForATitle` | that searching from the phone is coming, in household words |
| Requests | `WhatYouAreOwed` | what the house says they are owed, in the core's words, and what they asked for |
| Profile | `YourCornerOfTheHouse` | Switch house, App settings in household words, and taking the house off the phone |

Every member screen draws its way around through `FindsItsWayAroundTheHouse`:
the platform's top bar with the screen's title, and the bar the household's
`screen-closes` draws, shown on a tab's screen and hidden on one opened over a
tab. Changing house is a sheet on Profile listing the houses by name alone,
because how a house's machine stands is the operator's to read. The operator's
screens are never built for a member's session: the composition root builds
Home in their place.

**Home leads with what is theirs.** `WhatYouCanWatch` asks for two things: the
member's own requests and their shelf. `HowTheirOwnTitlesRead` turns the
requests into *Ready for you*, what has arrived in whole or in part, and *On
its way*, what is waiting for a yes, being fetched or partly here. Which
standing belongs to which row is `Waiting`'s to say. A request is a poster
with where it stands at the top, and it opens nothing, since a request names
no title on the shelf. Where those could not be asked for and the shelf
answered, Home says so in their place with Ask again.

**Then the house's own.** The hero comes next: the shelf's first title, which
is the newest in the house by the core's own order, lettered across a 16:9
tile, with Play and More. Play is drawn and cannot be used, with the app's own
sentence beside it, because the core hands the app no way to play a title.
More opens the title's screen. A member with nothing of their own sees the hero
first. After it, `HowAShelfReads` draws the shelf as rows: *New in the house*,
the first `HowAShelfReads::NEW_IN_THE_HOUSE` titles in the core's order, and
then one row for each kind the shelf holds, in the order `Medium` declares the
kinds. A row with nothing in it is not drawn, and a row shows what the read
returned, with nothing leading on to a longer list.

**Every title is a lettered poster.** A `Poster` is a raised 2:3 tile with a
line at the top and the name lettered at the bottom, at the step
`HowAPosterIsLettered` reads off the name's length, with nothing under the
tile. A screen reader hears its label once. The core serves the app no
artwork, so every title is drawn lettered. A title's poster opens
`WhatThisTitleIs`, handed the name, kind and year the shelf said, since the
core answers no reading for one title: it draws the poster and Play, drawn and
not usable, with its reason.

**The operator previews the member's side here too.** `WhatAMemberWouldSee`,
opened from *View as member* in the operator's menu, draws Home, with the hero
and the same rows and none of anybody's own, opening nothing, and Requests as somebody invited with the household's defaults would see
them, in the member's theme, from the core's answers for those defaults. They
are read for nobody, so the preview names no member, and it asks for nothing:
the control a member asks with is drawn and cannot be used. Its mark carries the
way back to the operator's screen it was opened from, and a member's session is
given Home in its place.

The module was empty for a long time and the reason is worth keeping, because it
is what decides the shape of what is here now. `lemonfiber` minted one token for
the run and exchanged one password for one session, and the `admission` envelope
said what the session was — `{token, until}` — and not who held it. Every caller
carrying it was the operator, so there was nobody to render a member's reading
to, and a surface built anyway would have been a screen that looked right and
was not.

## What answers the entitlement rules now

| | |
|---|---|
| `N3-R2` | What a member may do MUST be the core's answer |
| `N3-R3` | A control a member is not entitled to MUST be refused by the core if it is ever reached, and MUST NOT rely on the app having omitted it |

**Nothing here hides a control on entitlement grounds.** The app asks, the core
answers, and where the answer is *no* the app says so. A stack refusing a
request this account may not make arrives as `KindOfObstacle::NotForThisAccount`,
which carries its own sentence and its own remedy and is told apart from a
refused credential and from a stack that did not answer — three refusals, three
screens. `tests/Contract/OwingContractTest.php` holds the reading of it and
`app-modules/sdk/tests/Internal/WhatARefusalMeantTest.php` holds the mapping.

**The distinction the surface turns on is a refusal against an empty answer.**
Both arrive with no sentences in them and they are opposite things to read: one
says there is nothing to tell you, and the other says this was not yours to ask.
Drawing a list for both is how an app passes a refusal off as an absence, and
`WhatTheyAreOwed` is two arms rather than a collection so that a screen cannot.

## What answers the allowance rules

| | |
|---|---|
| `N3-R4` | Before a member asks for something, the app states whether it needs approval and whether they have allowance left |
| `N3-R5` | A member whose allowance is spent is told before asking, with when it resets |

`household.members[].to_hand_over` carries both, written to the member rather
than about them: what happens to what they ask for, what their period has left
and when it makes room again, what is still waiting, and what was refused and
why.

**They are rendered and never composed.** The same facts are on the wire in
parts — `asking.policy`, `asking.standing`, `films.remaining`,
`television.remaining`, `asking.frees_up` — and a surface assembling its own
wording out of them would be a second voice able to disagree with the core's
about the household's rules. That is a permission model with a template around
it, which is what `N3-R2` refuses. So the port answers with `Sentences` and this
module prints them.

## What answers the rule about which application

| | |
|---|---|
| `N3-R1` | The application a person is given is decided by the identity that signed in, and is not a setting or a separate build |

The admission carries `member`, `Whose` holds that subject beside the session,
the secure store keeps the two together, and two screens turn it into a surface.
Signing in leads where the subject says — `SignIntoAStack::onwardsTo()` — and so
does tapping a stack on the list a launch later, through
`YourStacks::tappingGoesTo()`. Both spell it as a `match` over an enum a fold
built, so neither screen holds a second copy of the rule and neither can drift
from the other without a test saying so.

**Two screens rather than one, because a session outlives the app.** Signing in
is the moment the subject arrives and the launch is every moment after it, and
an app that decided only at the first would hand a member the operator's machine
report every time they reopened it. The store was already holding the answer;
what was missing was anything reading it there.

**Neither screen is handed a session to find out.** `Resumed::whoseItIs()`
answers the subject without the secret, which is what lets a screen that speaks
to no stack decide which application somebody is given while never holding a
credential — the discipline `isSignedInto()` keeps by dropping what it is
handed, made available to a caller that wants an answer rather than nothing.

**There is no setting and no second build.** The one input is what the stack
said about whose session it opened.

## What answers the rule about their own requests

| | |
|---|---|
| `N3-R6` | A member's own requests carry their state in household terms, and expose no pipeline internals |

`WhatYouAreOwed` shows them beneath the sentences: the sentences say what
happens when they ask, and this says what became of the times they did. Each row
carries what was asked for, where it stands, and the reason it was refused where
there was one.

**Their own, because the core narrowed it, and refused rather than filtered
where it did not.** A member's session is answered with that member's row —
`lemonfiber-api` rewrites the command to their account id and discards whatever
the request named — so nothing here picks a row out of a house.
`Households::theirOwnIn()` takes exactly one row or answers with nothing, which
is the part worth keeping: filtering would behave identically every day the core
behaves, and on the day it did not it would hand one member another member's
requests. Refusing is the only reading that cannot quietly become this app
deciding who is looking.

**A state and never a stage.** No queue position, no percentage, no service
doing the fetching, no library handle for the thing. `WhatOneOfTheirRequestsSays`
holds three fields and there is a test that it holds only those — a field for a
requester is a field that could one day carry somebody else's name.

**Said in their words.** `Waiting::saidToTheMember()` beside
`saidOnTheScreen()`, because one of the seven states changes meaning rather than
phrasing: *waiting for your decision* is true to an operator and false to the
person who asked.

The reading is the operator's own parsing with the subject changed rather than a
second copy of it, so a member and an operator cannot be told different things
about the same refusal.

## `N3-R9` — what a member is never shown

> A member MUST NOT be shown lifecycle controls, logs, credentials,
> diagnostics, or another member's requests.

`tests/Feature/WhatAMemberIsNeverShownTest.php` refuses a member-facing type
that holds an operator's, and it was written **before** this module held
anything — a rule added afterwards is a rule written around whatever is already
there.

Credentials are the clause that needed care, because a surface that could name
none of them could not ask a stack anything. A `Credential` is refused outright:
it is the password, it exists for one exchange, and no member screen performs
that exchange. A `Session` is not, because every screen that reads a stack is
handed one — so the line is drawn where the risk is, at keeping one or handing
one on.

## `N3-R2` — the app implements no permission model

> The app MUST NOT implement its own permission model; what a member may do MUST
> be the core's answer.

The way this is broken is never a decision. A screen needs to know whether to
draw a button, the core's answer has not arrived yet, and somebody writes the
obvious thing — a check against a role, a flag, a list of what this kind of
member may do. It works, it is fast, and it is a second permission model that
drifts from the real one the first time the core changes its mind.

Nothing in this module reads `access`, `administrator`, `disabled`, `libraries`
or `restriction`. The one question it asks has one answer and the core writes
it.
