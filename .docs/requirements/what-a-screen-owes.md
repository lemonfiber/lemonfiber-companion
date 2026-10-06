# What every screen owes

The rules that apply to a screen whatever it is about. The code is
`app-modules/operator`, and these are the ones that turn up in nearly every file
in it.

Each row says what the requirement asks and what in this repository answers it.
The spec is canonical; where this page and a requirement disagree, the
requirement is right and this page is a defect.

## A screen an operator is not stuck on

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N1-R3` | A control is not hidden because a stack is unreachable — the app offers it and reports the failure | every screen's *ask again*; `tests/Templates/AnObstacleNeverTakesTheActionAwayTest.php` |
| `N1-R27` | A screen an operator cannot ask again is one that relies on leaving and returning. A screen whose content changes while open refreshes on a cadence it declares, and one whose content does not never polls | every screen's *ask again*; `tests/Templates/EveryReadingCanBeAskedAgainTest.php`. every screen says which with `#[ItsContent(WhatItShowsDoes::…)]`. What moves second by second looks again at `HowOftenAScreenLooks::WhileItMoves` through `LooksAgainWhileItMoves`, and what changes slowly at `HowOftenAScreenLooks::WhileOpen` through `LooksAgainWhileOpen`; each re-reads only the screen's own reading. `tests/Arch/EveryCadenceIsDeclaredTest.php` holds every screen to what it says, and the listed screens to their cadence. What a screen's chrome listens to is not the screen's content and is left out: the list of stacks the top bar's name opens, and the stack's stream every operator's screen about a stack holds through `HoldsItsStacksStream` to mark the bar, except on `HowThisStackIs`, whose content that stream is, and on `HowTheServicesAreWired`, which draws What answers what again from the stream's `wiring` event whenever the stack says it changed (`WiringTheServicesTest`) |
| `N1-R44` | An obstacle has a screen of its own rather than an empty frame | the empty keys a template branches on |
| `N1-R46` | A stack that could not be asked is told apart from a credential that was refused | `HowTheSignInWent` and the obstacle folds |
| `N1-R10` | Each obstacle is its own condition with its own remedy | `Obstacle`; `tests/Feature/EveryObstacleSaysSomethingOfItsOwnTest.php` |

## A screen that knows the way around

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N28-R1` | Every screen about a stack has the stack's name in the top bar and a menu control | `FindsItsWayAroundAStack` in `app-modules/wayfinding`, which the operator's screens take through `FindsItsWayAround` and a member's through `FindsItsWayAroundTheHouse`; `tests/Arch/EveryScreenAboutAStackHasTheMenuTest.php` holds every screen in both surfaces that answers which stack it is about to it |
| `N28-R2` | The stack's name opens the list of stacks | `ChoosesAStack`, the sheet every screen about a stack carries through that trait |
| `N28-R6` | The menu begins with the current stack and *What's new* with its count, and ends with the stack's settings and App settings | `the-menu` draws the stack's name, the way to another stack and What's new first, and Stack settings and App settings last (`OpeningTheMenuTest`). The count is the current stack's: every operator's screen about a stack holds its stream through `HoldsItsStacksStream`, which asks `Noticing::howMuchIsNew()` for new updates, requests and problems by what the stack names as newest, up to ten of each, against what the operator has seen; a kind switched off or read for the first time counts nothing. `Noticing` holds what each stack last named, and its count, in `WhatEachStackLastNamed`, a container singleton that lives in the process alone and is never written, sealed or kept: a screen that opens asks `Noticing::howMuchWasLastNamed()` and draws the last count any screen heard before its own stream wakes, and the stream replaces it once it names the newest. Every write to what is kept of a stack's news, marking items seen or Mark all seen among them, recounts it from what is kept, and removing the stack or clearing saved data forgets it. `FindsItsWayAroundAStack` hands `howMuchIsNewHere()` to `TheWhatsNewInTheMenu`, and the row draws `HowMuchIsNew::howManyInAll()` as `badge` between its label and its chevron, drawn on each platform by `scripts/patch_nativephp.php`, and says it to a screen reader as `news.new_on_tab` with the row's label; nothing new draws no count. The row opens What's new on this stack through `openWhatsNew()`, handing `AScreenWithoutAStack::WHATS_NEW_SHOWS`, and the operator can widen it. A member's screens draw no menu (`CountingWhatIsNewTest`, `NoticingTest`, `SeeingWhatIsNewTest`, `WhatEachStackLastNamedTest`) |
| `N28-R13` | A screen opened over another offers the platform's own way back, its back control and on iOS the edge swipe, and one with nothing beneath it offers none | On a screen about a stack, `FindsItsWayAroundAStack` asks the router whether a screen lies beneath it. Where one does, the menu control sits beside the platform's back control and the edge swipe is left to go back rather than open the menu. A tab, and what a member is owed, never has one, and no other screen at the bottom of the stack does either, signing in included. On a screen without a stack, `HasAWayBack` asks the same of the router and its top bar draws the back control, with the iOS edge swipe added by `scripts/patch_nativephp.php`. `GoingBackFromAScreenAboutAStackTest` and `GoingBackFromAScreenWithoutAStackTest` hold both |
| `N3-R9` | A member is not shown lifecycle controls, logs, credentials or diagnostics | a member's screens carry four tabs and no menu (`FindsItsWayAroundTheHouse`); the operator's menu follows whose session this phone holds for the stack, and only the operator's adds what is new and the stack's own screens (`TheRowsOfTheMenu`); an operator's screen about a stack asked for on a member's session builds the member's Home in its place, whatever asked for it (`OutOfTheOperatorsScreens`) (`OwingContractTest`, `SeeingWhatYouAreOwedTest`, `WhatAMemberIsNeverShownTest`, `AMemberNeverOpensTheOperatorsScreensTest`, `FindingYourWayAroundTheHouseTest`) |
| `N28-R14` | Every screen of the member's application opened as a preview carries, in the mark that says it is one, a control back to the operator's screen it was opened from, beside the platform's own way back | `ThePreviewMark` heads both tabs of `WhatAMemberWouldSee` and carries *Back to Switchboard*, which goes back one step. The two tabs are a choice on one screen opened over the operator's, so one step back from either is the operator's screen, by the mark's control or the platform's (`PreviewingTheMembersSideTest`) |
| `N2-R25` | The member's application, as somebody with the household's default access and allowance would see it, in the member's theme, marked as a preview, and with no named member's requests, allowance or watch history | *View as member* in the household group of the operator's menu (`TheMenu::ViewAsMember`) opens `WhatAMemberWouldSee`: the member's Home and Requests, drawn in the member's theme because the screen is `DrawnAsAMemberSeesIt`. Its shelf and its sentences come from `Watching::theDefaultShelf()` and `Owing::whatTheDefaultsAreTold()`, which ask the core for the household's defaults with `AsTheHouseholdsDefaults` and name no member, and nothing it reads is kept. A member's session asking for it is given Home in its place (`OutOfTheOperatorsScreens::ONLY_THE_OPERATOR_OPENS`), and one that reached it anyway asks nothing (`PreviewingTheMembersSideTest`, `DrawnAsAMemberSeesItContractTest`, `WatchingContractTest`, `OwingContractTest`, `AMemberNeverOpensTheOperatorsScreensTest`) |
| `N2-R26` | The preview sends no request, allowance spend or other change to the stack; a control that would is drawn, cannot be used, and says why | the preview reads and never writes; on its Requests tab the control a member asks with is drawn disabled, with `household.preview.cannot_ask` beneath it (`PreviewingTheMembersSideTest`) |

## A screen that does not talk to a machine behind your back

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N1-R65` | One reading per frame, and the screen renders what came back | every screen holds what one asking produced, and a screen with a second reading takes it on the next frame through `ReadsAStackOnceAFrame`; `tests/Feature/EveryScreenTheRouterServesDrawsTest.php` draws three frames of every screen the router serves and counts what each read; `tests/Arch/EveryCadenceIsDeclaredTest.php` |
| `N1-R66` | Beyond that, only a declared cadence or an operator's act — never a value read, a key pressed, or a screen rebuilt | the screens that poll declare their `HowOftenAScreenLooks`, and poll only while something is settling |
| `N1-R24` | A session lives no longer than the reach it was made for | opening a screen is a reach, and it carries when it was read |

## What a screen may not leak

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N1-R8` | A credential never appears in a URL | routes carry a stored id and never an address or a secret |
| `N1-R15` | An address has one destination and a screen is not it | a row is a name and nothing else; `tests/Templates/NothingSecretReachesAScreenTest.php` |
| `N4-R13` | A diagnostic report is assembled from what the operator chooses to send, not from whatever a screen happened to hold | `Concealed` on every stack-facing screen (`DiagnosticsTest`, `SharingContractTest`, `TheFirstFrameIsOfferedTest`, `ADiagnosticReportSaysNothingSecretTest`) |
| `N4-R18` | Credentials and pairing material are kept out of a capture, and so is the report | `tests/Arch/NothingIsCapturedFromAGuardedScreenTest.php` (`ConcealedTest`, `CaptureContractTest`, `EveryPluginThisAppShipsIsAdmittedTest`) |

| `N1-R43` | A refused attempt leaves the action offered — the attempt failed, the capability did not become unavailable | `Attempted`, which has no arm that withdraws what was tried; `AnActionIsNeverHeldTest` is what stops a screen inventing one |

## What a screen looks like

| Requirement | What it asks | What keeps it |
|---|---|---|
| `G3-R16` | On a surface operated by touch, every control presents a target at least as large as the platform's own stated minimum | `EveryTargetIsBigEnoughToHitTest`, which reads the templates rather than trusting a component to have been used |
| `DES-R15` | The accent is not set as text — measured at 1.6:1, it fails | `tests/Templates/ThemeColourIsNotSetAsTextTest.php` |
| `DES-R29` | The member theme draws on the ink theme's `canvas`, raises on `ink-soft` and sets text in `paper` and `text-muted`, with `lemon` and `ink` on it as the accent, whatever the phone is set to | `ThemeToken::in()`; `app-modules/design/tests/Api/ThemeTokenTest.php`, `tests/Arch/BrandPaletteParityTest.php` |
| `DES-R30` | The operator theme draws on `ink` with `line` hairlines, raises on `ink-soft` and sets text in `paper`, `text-muted` and `text-faint`, whatever the phone is set to; `lemon` is the operator's own actions, `fiber` is activity, and how a thing stands is drawn in the severity tokens, each with a shape of its own | `ThemeToken::in()`; `app-modules/design/tests/Api/ThemeTokenTest.php`, `tests/Arch/BrandPaletteParityTest.php`. `Tone::colour()` paints each state's glyph in `ok`, `fiber` as warning, `alarm`, or `fiber` as activity, and `Tone::ground()` raises a notice about something that wants looking at on `warn-tint` and about something broken on `alarm-tint`. `standing`, `notice`, `marked-line` and a toned `row` take both from the tone; a row's glyph is coloured on iOS through `scripts/patch_nativephp.php` (`ToneTest`, `NoticeTest`, `StandingTest`, `MarkedLineTest`, `RowTest`, `ARowsGlyphTakesItsColourTest`). `ThemeToken::OwnAction` sets the operator's quieter actions in `lemon` on the operator's screens and in muted text in the member's theme, the one role that paints lemon as words, and `ThemeToken::OnAlarm` draws the glyph on an alarm-filled port in ink (`ThemeTokenTest`, `PortTest`, `QuietActionTest`) |
| `DES-R36` | Every surface that draws severity takes it from the brand's `ok`, `alarm`, `warn-tint` and `alarm-tint`, with `fiber` as warning | the severity roles of `ThemeToken`, each checked against the ink theme's value in `tokens.json`; every glyph at 3:1 on each ground it sits on. The member's theme paints those roles as text and as the raised surface, so it draws no severity colour (`ThemeTokenTest`, `BrandPaletteParityTest`) |
| `DES-R33` | Every colour, spacing, radius and type value comes from the brand's tokens and none is hardcoded; radii are `sm` and `md`, with the pill on a chip or a button alone | `ThemeToken`, `Radius`, `TypeSize` and `Typeface` copy the brand's values, which a module may not read from a file, and `tests/Arch/BrandPaletteParityTest.php`, `tests/Arch/BrandMeasuresParityTest.php` and `tests/Arch/TheBundledFacesAreTheBrandsTest.php` hold them to `tokens.json`. Every gap, padding, margin, corner and text size a template draws is read back through the parser and held to the same file; the class lists are literal (`tests/Arch/NoClassDecidedAtRuntimeTest.php`, `tests/Templates/BladeHoldsNoLogicTest.php`) |
| `DES-R32` | Interface text is set in Golos Text and figures, identifiers, timestamps and log text in DM Mono, both bundled and never fetched at run time; text scales with the platform's text size; Bricolage Grotesque is not set as text | `Typeface` names every bundled face and each text element names one: `tests/Arch/EveryTextIsSetInABundledFaceTest.php`, and `tests/Arch/TheBundledFacesAreTheBrandsTest.php` for the files, their families, their weights, their licences and nothing fetching a face. A size is the brand's at the platform's default text size, which iOS scales with Dynamic Type and Android with its font scale |
| `G3-R1` | Two things are not told apart by colour alone | every `Tone` has a glyph of its own on both platforms as well as its colour, and the words beside it say the state; a class list cannot change with a state, so a state's colour is only ever a glyph's or a notice's ground beside them (`ToneTest`, `NoClassDecidedAtRuntimeTest`) |
| `N3-R24` | Every title carries its name as text, and a title the core serves no artwork for is drawn as a poster lettered with its name | `Poster` and `Hero` in `app-modules/household` draw each title as a raised tile with its name lettered on it at the brand's size `HowAPosterIsLettered` picks from the name's length, and the label says the name to a screen reader as one element; the core serves the app no artwork, so every title is drawn lettered (`PosterTest`, `HeroTest`, `HowAPosterIsLetteredTest`, `SeeingWhatYouCanWatchTest`) |
| `N3-R23` | Home leads with the member's own titles, and a shelf with nothing in it is not drawn | `WhatYouCanWatch` draws *Ready for you* and *On its way* from `HowTheirOwnTitlesRead` first, then the hero and the shelf's rows; a row with nothing in it, its heading included, is not drawn (`ShelfRow::shouldRender()`). What a member was part-way through is not read: the core serves no such reading, so that row is not drawn (`SeeingWhatYouCanWatchTest`, `ShelfRowTest`) |
| `N3-R22` | A title's page carries one primary action that follows its state, never hidden | `WhatThisTitleIs` draws Play for a title on the shelf, drawn and not usable, with the reason beside it in the app's own words: the core serves the app no way to play a title (`OpeningATitleTest`) |
| `Q-R64` | A screen publishes at most twenty methods | `tests/Arch/ModuleApiTest.php`; three screens arrived at twenty-one the day this was written |

## What a screen says

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N1-R1` | Parity across surfaces, rather than parity by everybody remembering | the catalogues, and the rules over them |
| `N1-R2` | **Every action available from another surface is offered here**, unless a requirement says otherwise and why | **Not kept.** See *What is not built* below (`EveryActionTheStackOffersTest`, `PairingAStackByScanningTest`, `PairingAStackByTypingTest`, `SeeingHowAStackIsTest`, `SigningIntoAStackTest`, `TheFirstFrameIsOfferedTest`) |
| `N2-R1` | The app opens on the overall verdict, and says `unknown` as its own answer | `YourStacks`, whose every row says how that stack stands in the one line's own sentence, `HowTheOneLineReads`. A stack never heard reads unknown |
| `N2-R2` | Worst first, ordered here rather than trusted to arrive that way | `WorstFirst`, reached by `HowThisStackIs` (`WorstFirstTest`, `SeeingHowAStackIsTest`) |
| `N2-R3` | A finding carries its meaning and its remedy, in the core's own words, and its code one step away | `HowAFindingReads`; `WhatOneFindingSays::carriedToTheLogs()` hands a service finding's code to its logs, which `WhatThisServiceSaid` says above the lines, and `codeAtTheFoot()` keeps a machine finding's code in the detail at the foot of its card |
| `G4-R3` | The cause is reported rather than each symptom independently | narrow, sort, then group — grouping last (`TheCauseBeforeItsSymptomsTest`) |
| `G4-R4` | Plain explanation leads; technical detail is available and does not lead | it arrives on the row beneath everything above it |
| `N1-R9`, `N2-R13` | An age comes out beside the word or not at all | both are broken by omission rather than by disagreement |
| `N2-R14` | A value the contract did not carry is not substituted | *nought seconds* is the worst answer, and is refused |

## What is not built, and what holds it open

`N1-R2` is a parity rule, and it is the widest requirement this app answers:

> Every action available from another surface MUST be offered by the app, except
> where a requirement here states otherwise and why.

**It is not kept, and this page said it was.** The row above used to read *an
operator away from the machine can see whether their house is working*, with
*the whole module* as what keeps it. That is a sentence about seeing, and the
requirement is about doing — so the one rule that would have caught the gap
below was written down as something it is not. This page's own header says the
spec is canonical and a disagreement is a defect here; this was one.

**The measurement.** The SDK ships 71 envelopes and this app follows 62.
Of the rest, 8 are never named by the code in `app-modules` or `bridge`, tests
aside, and 1 more — `Pull` — is named without being followed. `Admission` is
followed through the SDK: signing in opens a door whose class reads that
envelope itself, so no reader here opens it.
They resolve to the features below — each one an action available from another
surface and not offered here, or offered only in part.
`tests/Feature/EveryActionTheStackOffersTest.php` classifies every kind the SDK
ships as offered, excused by a requirement, or not yet offered, and holds these
counts to what those lists come to.

`A2` is not in the list: first-run setup is the one exception `N1-R2` allows
for, and `N1-R4` states it and why — a phone cannot perform the act that makes a
phone able to perform acts.

Nothing here is a commitment to build a screen. `N1-R17` still holds: a field
wants a requirement before it wants a surface. What each row needs is a decision
— a companion surface, or a requirement stating why not, the way `N1-R4` does
for setup.

**An unread envelope is not an unbuilt feature, and two rows prove it.** `D7`
and `F7` are each documented on these pages already, beside an envelope the
app does not read. So those two are *partly* built, and the question they ask
is narrower than the rest: not whether this app does the thing, but whether it
reads everything the wire now says about it.

**Twenty-four more are partly built, the other way round:** the envelope is read and
drawn, and it answers some of the feature's requirements rather than all of
them. `B2` is starting, stopping and restarting a service or a form, and fetching a form's images ahead of a start, followed to what the stack reports it came to, with what a running start is waiting for: what did not come back, a start it declined with its reason, a rehearsal, what was left out and which ports something else holds (`N2-R7`, `N2-R24`, `N16-R6`); `A5` is the survey of what is already on a machine: every project and service, what is in the way, what cannot be taken over, what the layout costs, and the modes as the stack offers them, and moving in by each: what it would come to before the yes, what an import could not carry, the stance as given, a refusal with its reason, and what is copied first (`N7-R1` to `N7-R6`, `N7-R9`, `N7-R11` to `N7-R14`); `D1` is wiring the services to each other: every connection in the state the stack gave it, a value changed by hand said to be kept, both values where both moved, a service's rejection in its own words, what a warning breaks and what puts it right, whether drift could be judged, and a rehearsal labelled as one, what answers each capability a service asks for, how that was settled and where each claimant came from, and choosing who answers one two or more services claim: what answers it now and what would after, what asks for it and what the choice would leave unfilled before the yes, with the operator's reason (`N7-R7`, `N7-R8`, `N7-R10`, `N7-R15` to `N7-R17`, `N5-R1` to `N5-R7`, `N5-R12`, `N5-R13`); `A7` is the credentials a stack holds, where each stands, who made it and what uses it, and never a value (`N9-R1` to `N9-R4`); `G5` is the front door, what each address faces and why, and whether it was chosen (`N9-R9` to `N9-R11`); `G6` is which app to watch on, device by device, with its rating, whether it is open source, and what to use instead (`N9-R8`, `N9-R9`); `G9` is connecting one member's device: the address as a code and as text, the steps and the apps, the devices signed in, and what there is to do next (`G9-R2` to `G9-R7`, `G9-R9` to `G9-R11`, `G9-R13`); `D6` is inviting somebody, what the invitation grants and when it lapses before it is sent, handing it over, taking a password off, and taking somebody out, with what it costs before it is agreed to and how far it reached (`N9-R5`, `N9-R6`, `N21-R1` to `N21-R10`, `N13-R1` to `N13-R3`, `N13-R9`, `N13-R11`, `N13-R19`); `B1` is a stack running some of its forms, and what starting a form would come to (`N18-R1`, `N18-R3` to `N18-R9`); `A6` is what the stack keeps on the machine, secrets named and never shown, and taking lemonfiber off it: four removals, each read before it is agreed to, how much of the reading was read, what is not lemonfiber's, what is kept and why, what is still coming down, what lemonfiber cannot take, and what a removal took, destroyed and left (`N6-R7`, `N13-R4` to `N13-R7`, `N13-R9` to `N13-R18`, `N13-R20`); `B5` is the alert preset and its exceptions (`N10-R8`); `B10` is what
keeps running when nobody is signed in, what each command guarantees and what
did not come back, and handing a command over or taking it back with what each did (`N10-R10`, `N23-R1` to `N23-R5`), and, apart from hosting, a guard on the data location held only while its screen asks and let go when it is left (`N23-R11` to `N23-R13`); `D9` is where one item got to, followed through the services (`N8-R4` to `N8-R6`, `N8-R8`, `N8-R9`); `D2` is the quality presets in force, choosing one overall or per kind of media, a held choice confirmed apart, and upgrading what is already here described kind by kind before it is carried out (`N24-R1` to `N24-R5`, `N24-R10`); `D3` is the first-content walkthrough, started from the phone, followed stage by stage as it runs and to its record (`N15-R5` to `N15-R8`); `G2` is the glossary, each word explained where it is drawn and searchable by what else it is called, and one word the held glossary lacks asked of the stack by the operator (`N15-R3`, `N15-R4`, `N15-R9` to `N15-R11`); `D5` is how full the machine is, where the room went, each download with where it stands, and stopping seeding one download with what it costs, a rehearsal labelled as one (`N12-R1` to `N12-R10`); `D10` is the line's capacity and cap (`N10-R4` to `N10-R7`); `E2` is which version runs, how it was installed and what moving it would take, and which versions run under it with what the running release changed (`N14-R1` to `N14-R8`); `E4`
is the record of what was changed and how far back it goes, and putting a run
back from it: what goes with the run said from the record's own rows before the
yes, and the stack's report leading with what it left and why (`N11-R1` to
`N11-R3`, `N11-R9`, `N11-R10`, `N6-R6`); `E3` is the list of copies, taking one and putting one back: an empty list told apart from one that could not be read, the scope named before and after, what a copy removed, how its size stood against the minute, a rehearsal labelled as one and where a restore put the data, and putting the configuration back, previewed file by file and connection by connection before the yes (`N6-R1` to `N6-R5`, `N6-R8` to `N6-R10`); `C4` is a support bundle, chosen, described before it is written and read in full here, with a refusal drawn as one, and handed over by the operator through the phone's own sharing (`N22-R1` to `N22-R10`); `C7` is queue health: what stopped moving by kind, worst first, one row per cause with the service's own words and how long, slow drawn apart from stuck, what could not be read named, and each item leading to its trace (`N23-R6` to `N23-R10`); `G8` is what leaves the machine, ours and
theirs apart (`N10-R1` to `N10-R3`, `N10-R12`); `C6` is the material a phone is paired with, read,
compared and pinned here, and made on *Pair a phone* so another phone can pair, while
replacing the certificate it pins is not offered (`N1-R18` to `N1-R20`, `N1-R47` to
`N1-R51`, `N1-R74`). Each is kept on
[pairing a machine](pairing-a-machine.md),
[what leaves a machine](what-leaves-a-machine.md),
[moving in](moving-in.md),
[what a machine keeps](what-a-machine-keeps.md),
[taking lemonfiber off](taking-lemonfiber-off.md),
[asking for help](asking-for-help.md),
[how full a machine is](how-full-a-machine-is.md),
[what is running here](what-is-running-here.md),
[where an item got to](where-an-item-got-to.md),
[choosing how good](choosing-how-good.md),
[who gets in](who-gets-in.md),
[asking somebody in](asking-somebody-in.md),
[taking somebody out](taking-somebody-out.md),
[watching one thing arrive](watching-one-thing-arrive.md),
[what the words mean](what-the-words-mean.md),
[running part of it](running-part-of-it.md), [connecting the stack](connecting-the-stack.md),
[what a machine says](what-a-machine-says.md) or
[what was done here](what-was-done-here.md), and the rest of each feature is
still a decision nobody has made.

That leaves **twelve** with nothing documented at all.

| Feature | What it is |
|---|---|
| `A6` | Clean uninstall — **partly built**, see above |
| `A7` | Credential management & rotation — **partly built**, see above |
| `B1` | Forms & partial stacks — **partly built**, see above |
| `B10` | Hosting long-running commands — **partly built**, see above |
| `B2` | Lifecycle control — **partly built**, see above |
| `B5` | Notifications & alerting — **partly built**, see above |
| `B8` | Autostart & boot persistence |
| `C4` | Support bundle — **partly built**, see above |
| `C6` | Web UI security & binding policy — **partly built**, see above |
| `C7` | Queue health & stuck items — **partly built**, see above |
| `D1` | Service auto-wiring — **partly built**, see above |
| `D10` | Bandwidth & scheduling — **partly built**, see above |
| `D2` | Quality presets in plain language — **partly built**, see above |
| `D3` | First-content walkthrough — **partly built**, see above |
| `D5` | Disk space management — **partly built**, see above |
| `D6` | Household identity & invitations — **partly built**, see above |
| `D7` | Request approval & quotas — **partly built**, see above |
| `D9` | "Where is my show?" pipeline trace — **partly built**, see above |
| `E2` | Self-update — **partly built**, see above |
| `E3` | Backup & restore — **partly built**, see above |
| `E4` | Rollback — **partly built**, see above |
| `F4` | The capability vocabulary |
| `F6` | Plugin lifecycle |
| `F5` | The plugin catalogue and what vouches for a plugin |
| `F7` | Plugin provenance — **partly built**, see above |
| `F9` | Capabilities of the bundled services |
| `G2` | Plain-language layer & in-product help — **partly built**, see above |
| `G5` | The front door — **partly built**, see above |
| `G6` | Client app guidance — **partly built**, see above |
| `G8` | Privacy stance — **partly built**, see above |
| `G9` | Mobile client handoff — **partly built**, see above |
| `H1` | Cross-seeding |
| `H2` | Announce-driven grabbing |
| `H3` | Quality-profile sync |
| `H5` | Queue self-healing |
| `H6` | Library cleanup |
| `H8` | Playback statistics |
| `K1` | Metrics & dashboards |

**What each rule watches.** `WhatTheContractCarriesThatNothingReadsTest` holds
every path on an envelope this app reads to a reader or a listed reason; it
walks `WhatTheReadersRead::envelopes()`, so it sees only envelopes a reader
opens. `EveryActionTheStackOffersTest` holds the envelopes themselves: each one
the SDK ships is offered, excused or not yet offered, and the counts above are
its measurement.
