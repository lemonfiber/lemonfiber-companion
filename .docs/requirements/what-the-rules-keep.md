# What the rules keep

The requirements nothing in a module answers, because what answers them is a
rule that reads this repository from outside it: the architecture suites, the
template suites, and the feature tests that drive the app the way an operator
does.

They are collected here rather than spread across the module pages for the
reason they exist as rules at all. A requirement kept by a class has a class to
name; a requirement kept by *the absence of something* — no flashing, no
credential on a screen, no second accessibility layer — has nothing to point at
except the rule that would go red if it appeared. Put that row on a module's
page and it reads as a property of the module. It is not. It is a property of
everything.

Each row says what the requirement asks and what in this repository answers it.
The spec is canonical; where this page and a requirement disagree, the
requirement is right and this page is a defect.

## What an operator reads

| Requirement | What it asks | What keeps it |
|---|---|---|
| `G2-R3` | One concept uses one term consistently across every surface and message | `tests/Arch/TheNamesThisProductGivesTest.php` |
| `G2-R13` | Every acronym in shipped text resolves to a glossary entry, or to a word declared ordinary and carrying the reason it is | `tests/Arch/EveryAcronymAnOperatorReadsTest.php`, whose list of already-known words starts empty on purpose |
| `G2-R14` | The names this product gives its own things are declared, and every locale renders a declared name as declared and translates only the text around it | `tests/Arch/TheNamesThisProductGivesTest.php` — both word boundaries, because *formulier* begins with *form* |
| `G3-R10` | A value is not truncated in a way that changes its meaning | `tests/Contract/SayingContractTest.php` — a scrollback is always truncated, so what is asked is that the bound is stated |
| `G3-R14` | Severity is present as text, not only as colour | `tests/Arch/NoClassDecidedAtRuntimeTest.php` — a class list decided at runtime is how a line comes to carry its severity in colour alone |

## What a screen does to the person reading it

| Requirement | What it asks | What keeps it |
|---|---|---|
| `G3-R6` | Nothing flashes or blinks | `tests/Templates/NothingAScreenDoesToItsReaderTest.php`, by name rather than by what the parser happens not to support |
| `G3-R8` | Layout adapts to a small viewport without horizontal scrolling | `tests/Templates/NothingAScreenDoesToItsReaderTest.php`, narrowed to what cannot be right: a fixed device-pixel width, a viewport-width class, a sideways scroll container |
| `G3-R15` | Text the product did not author cannot alter what is drawn | `tests/Templates/NothingAStackSaidCanRedrawAScreenTest.php` — no unescaped echo anywhere |
| `N1-R25` | A screen that reads from a stack publishes its first frame before issuing the read, built from what the app already holds | `tests/Arch/ScreensDeclarePaintingTest.php` |
| `N4-R14` | The platform's text scaling, screen reader, contrast and reduced-motion settings are honoured, and no parallel accessibility layer is built | `tests/Templates/ThePlatformOwnsAccessibilityTest.php` — a duration written into a template is the one with no sensible default |
| `N4-R21` | Every control that can be operated carries a label the screen reader announces, and an icon is never the only thing carrying a control's purpose | `tests/Templates/ScreensSpeakToTheOperatorTest.php`, a table over every component rather than a list of the ones that matter |
| `N4-R24` | While the app is locked, no application content is drawn behind the lock — including a first-run surface, an empty state and any frame drawn before | `tests/Feature/NothingIsDrawnBehindTheLockTest.php`, asking the rendered tree rather than the template text |
| `DES-R18` | `fiber` and `fiber-light` are not used as text | `ThemeToken`, and `tests/Feature/TheAccentIsTheBrandsTest.php` |
| `DES-R26` | The platform's spacing, radii, elevation and motion are not overridden with brand values | `ThemeToken` maps the accent role and nothing else |

## What an error owes

| Requirement | What it asks | What keeps it |
|---|---|---|
| `G4-R1` | Every user-facing error states what happened, what it means, and what to do | `tests/Feature/EveryObstacleSaysSomethingOfItsOwnTest.php` — the second test is what makes the middle one true |
| `G4-R2` | Errors use exactly the four defined severity levels | `app-modules/kernel/tests/Api/SeverityTest.php` |
| `G4-R6` | Every error kind carries a stable identifier | `app-modules/kernel/tests/Api/ObstacleTest.php` |
| `G4-R8` | An error carries no credential and no secret | `tests/Templates/NothingSecretReachesAScreenTest.php`, held by being stricter: the three values are refused on every screen, not only on the ones somebody sees because something failed |
| `B2-R10` | Status distinguishes absent, stopped, starting, healthy, unhealthy, crash-looping and failed | `app-modules/kernel/tests/Api/HowAServiceRunsTest.php`, a register of all seven |

## What a member is never shown

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N3-R1` | The application a person is given is decided by the identity that signed in | `tests/Feature/SigningIntoAStackTest.php` at the tap and `tests/Feature/TheFirstFrameIsOfferedTest.php` at every launch after it. Two screens, because a session outlives the app being closed: deciding only at sign-in gave a member the operator's report on every reopen. `Resumed::whoseItIs()` answers the subject without the session, so neither screen holds a credential to decide it |
| `N3-R2` | The app implements no permission model; what a member may do is the core's answer | `tests/Contract/OwingContractTest.php` and `tests/Feature/SeeingWhatYouAreOwedTest.php` — the port answers with the core's own sentences, so there is nothing for a surface to compose a wording from |
| `N3-R3` | A control a member is not entitled to is refused by the core if it is ever reached, and does not rely on the app having omitted it | `app-modules/sdk/tests/Internal/WhatARefusalMeantTest.php` tells the refusal apart, `app-modules/kernel/tests/Api/ObstacleTest.php` holds what it says and that it signs nobody out, and `tests/Feature/SeeingWhatYouAreOwedTest.php` is what draws it as a refusal rather than as an empty reading |
| `N3-R4` | Before a member asks for something, the app states whether it needs approval and whether they have allowance left | `tests/Contract/OwingContractTest.php`, run against the adapter and the fake. The sentences are the core's and are carried unchanged — the wire has the parts as well, and composing from them would be a second voice about the household's rules |
| `N3-R5` | A member whose allowance is spent is told before asking, with when it resets | The same port and the same suite. The reset is `asking.frees_up` written into a sentence by the service that keeps the period, so reading it needs no arithmetic |
| `N3-R6` | A member's own requests carry their state in household terms | `tests/Contract/OwingContractTest.php` and `tests/Feature/SeeingWhatYouAreOwedTest.php`. The reading is narrowed by the core rather than filtered here: `Households::theirOwnIn()` takes exactly one member row or answers with nothing, so an answer about a house is refused rather than picked over |
| `N3-R7` | A refused request carries the reason that was given | The same two suites. The reason is the stack's own words carried onto the row and shown beneath its state, and a row that was not refused carries an empty one rather than none — so a template reading it has no branch to get wrong |
| `N3-R9` | A member is not shown lifecycle controls, logs, credentials, diagnostics, or another member's requests | `tests/Feature/WhatAMemberIsNeverShownTest.php` |
| `N3-R10` | Where a member's request failed on a stack fault, they are told it did not work and that the operator has been told — and are not shown the fault | `tests/Feature/WhatAMemberIsNeverShownTest.php` |
| `N3-R11` | Parental limits are rendered from the core's answer, with no second copy held here | Answered by absence: this app renders no limits. `tests/Arch/NothingPlaysMediaHereTest.php` names it in the refusal it prints, so whoever adds the first one is told where the answer lives — but nothing here would go red if a second copy appeared beside it |
| `N3-R12` | While the stack is unreachable, asking for something new is declined rather than queued | `tests/Arch/AnActionIsNeverHeldTest.php`, and `tests/Feature/WhatAMemberIsNeverShownTest.php` |

**`N3-R8` was withdrawn, and this repository still keeps it.** The row said the
app plays no media and hands off to a household client;
`tests/Arch/NothingPlaysMediaHereTest.php` reads the platform sources for a
player and refuses one. The spec has since reversed it — the app plays, because
sending somebody to a second application they must also install is a household
product stopping short of the thing the household wanted — and what the row
protected is now `N3-R14` and `N3-R16`: not *no player*, but a player holding no
second copy of a library, an age limit or an entitlement, and none of the asking
logic.

The test is left standing rather than deleted, and it now cites the rows that
replaced the withdrawn one. The choice is a trap either way. Deleting it would
drop the guard during exactly the window when somebody might add a player
carelessly — the erosion its own argument describes. Leaving it citing a row
that no longer exists would let it refuse the next feature on an authority it
had lost.

**It is stricter than `N3-R14` and `N3-R16` ask, and that is deliberate for as
long as it lasts.** Those two forbid a player that holds a second copy of a
library, an age limit or an entitlement, or that implements any of the asking;
this refuses a player at all. The reason the stronger form is the right one
today is that a narrower one would be unenforceable: nothing on the wire says
where to play a holding — `HeldEnvelope.holdings[]` carries an identifier and
no location — so a player added now could only work by composing an address out
of a stack's address and an id, which is the second copy `N3-R14` refuses and
is also the thing that breaks the day a library is reached from somewhere other
than the same network.

So the over-enforcement and the gap have one lifetime. The gap is recorded in
`tests/Arch/WhatTheContractDoesNotCarryTest.php`, which goes red the day a
location is carried. That is the day this is re-aimed at what actually
survived, and the day this paragraph is deleted rather than edited.

## What cannot be switched off

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N1-R21` | Certificate verification is not disabled in any build, under any flag or configuration value | `tests/Arch/NothingTurnsVerificationOffTest.php` and `tests/Arch/VerificationCannotBeTurnedOffTest.php`, reading every environment file rather than `.env.example` alone |
| `N2-R12` | The app never offers to set or change a credential's value | `tests/Arch/TheAppOpensOnlyTheseDoorsTest.php`, with `SupervisorsTest` and `tests/Feature/WhatAMemberIsNeverShownTest.php` on the two surfaces that could grow one |
| `N1-R58` | A stand-in sits below the transport the app's client uses, so the client, the envelope reading and the wire-version check exercised against it are the real ones | `tests/Feature/TheWholeWireAnswersWithNoStackOnItTest.php` |
| `N4-R16` | A local-network permission purpose is declared in the operator's terms, and is never a placeholder | `tests/Arch/PermissionsAreExplainedTest.php`, reading the built manifest rather than a constant |

## The floor under all of them

| Requirement | What it asks | What keeps it |
|---|---|---|
| `Q-R66` | A gate is shown to refuse the defect it exists to catch, in the environment it runs in, before it is relied on | Every rule that walks a set asserts it found one, across `tests/Arch`, `tests/Templates` and `tests/Modules`. A rule whose subject list went empty passes with no iterations, and a green run over nothing looks exactly like a green run over everything |

## What is not built, and what holds it open

These are named in the rules above as registers rather than as checks: the rule
passes while the gap is open and is where whoever closes it will look. A row
here is not a thing this repository does.

| Requirement | What it asks | What holds it open |
|---|---|---|
| `N3-R14` | What a member may watch is the core's answer, and the player holds no second copy of a library, an age limit or an entitlement | Not built: there is no player here yet. The shelf it would play from is blocked on the client, which carries the `held` envelope's shape and no endpoint to ask for it |
| `N3-R15` | Where the media server cannot be reached, playback is declined with the reason rather than queued or shown as buffering | Not built, with the player |
| `N3-R16` | The player implements no request, approval or allowance logic of its own | Not built, with the player. What it will be held to is what `N3-R2` already holds the rest of this surface to |
| `N1-R5` | Reconfiguration is offered in full once connected | The settings are not a list this side can know — `ConfigEnvelope` carries what the stack has, so a screen offering the settings it knows about offers a subset the day the stack adds one, silently. `tests/Feature/EveryActionTheStackOffersTest.php` says so rather than gating on a guess |

## What another repository answers

| Requirement | What it asks | Where it lives |
|---|---|---|
| `C6-R19` | Replacing a certificate a companion may have pinned is announced before it is replaced, naming re-pairing as the consequence | The stack's side, not this app's. It is named in `tests/Feature/NothingReachesAStackUnpinnedTest.php` because it answers the cry-wolf worry this app's pinning raises: a pin that breaks without warning is one an operator learns to click past |
