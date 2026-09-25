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
| `N1-R27` | A screen an operator cannot ask again is one that relies on leaving and returning | every screen's *ask again*; `tests/Templates/EveryReadingCanBeAskedAgainTest.php` |
| `N1-R44` | An obstacle has a screen of its own rather than an empty frame | the empty keys a template branches on |
| `N1-R46` | A stack that could not be asked is told apart from a credential that was refused | `HowTheSignInWent` and the obstacle folds |
| `N1-R10` | Each obstacle is its own condition with its own remedy | `Obstacle`; `tests/Feature/EveryObstacleSaysSomethingOfItsOwnTest.php` |

## A screen that does not talk to a machine behind your back

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N1-R65` | One reading per frame, and the screen renders what came back | every screen holds what one asking produced; `tests/Arch/EveryCadenceIsStatedTest.php` |
| `N1-R66` | Beyond that, only a stated cadence or an operator's act — never a value read, a key pressed, or a screen rebuilt | the screens that poll say how often, and only while something is settling |
| `N1-R24` | A session lives no longer than the reach it was made for | opening a screen is a reach, and it carries when it was read |

## What a screen may not leak

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N1-R8` | A credential never appears in a URL | routes carry a stored id and never an address or a secret |
| `N1-R15` | An address has one destination and a screen is not it | a row is a name and nothing else; `tests/Templates/NothingSecretReachesAScreenTest.php` |
| `N4-R13` | A diagnostic report is assembled from what the operator chooses to send, not from whatever a screen happened to hold | `Concealed` on every stack-facing screen |
| `N4-R18` | Credentials and pairing material are kept out of a capture, and so is the report | `tests/Arch/NothingIsCapturedFromAGuardedScreenTest.php` |

| `N1-R43` | A refused attempt leaves the action offered — the attempt failed, the capability did not become unavailable | `Attempted`, which has no arm that withdraws what was tried; `AnActionIsNeverHeldTest` is what stops a screen inventing one |

## What a screen looks like

| Requirement | What it asks | What keeps it |
|---|---|---|
| `G3-R16` | On a surface operated by touch, every control presents a target at least as large as the platform's own stated minimum | `EveryTargetIsBigEnoughToHitTest`, which reads the templates rather than trusting a component to have been used |
| `DES-R15` | The accent is not set as text — measured at 1.6:1, it fails | `tests/Templates/ThemeColourIsNotSetAsTextTest.php` |
| `DES-R24` | One platform mapping, decided once rather than at each call site | the class lists are literal; `tests/Arch/NoClassDecidedAtRuntimeTest.php` |
| `DES-R25` | Two things are not told apart by colour alone | the same rule, which also keeps `G3-R1` |
| `Q-R64` | A screen publishes at most twenty methods | `tests/Arch/ModuleApiTest.php`; three screens arrived at twenty-one the day this was written |

## What a screen says

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N1-R1` | Parity across surfaces, rather than parity by everybody remembering | the catalogues, and the rules over them |
| `N1-R2` | **Every action available from another surface is offered here**, unless a requirement says otherwise and why | **Not kept.** See *What is not built* below |
| `N2-R1` | The app opens on the overall verdict | `HowThisStackIs` |
| `N2-R2` | Worst first, ordered here rather than trusted to arrive that way | `WorstFirst`, reached by `HowThisStackIs` |
| `N2-R3` | A finding carries its code, its meaning and its remedy, in the core's own words | `HowAFindingReads` |
| `G4-R3` | The cause is reported rather than each symptom independently | narrow, sort, then group — grouping last |
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

**The measurement.** The SDK ships 62 envelopes and this app follows 28.
Of the rest, 32 are never named by the code in `app-modules` or `bridge`, tests
aside, and 2 more — `Admission` and `Pull` — are named without being followed.
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

**An unread envelope is not an unbuilt feature, and four rows prove it.** `B2`,
`D7`, `F7` and `G2` are each documented on these pages already: lifecycle
control is `N2-R7`, kept by `Daemon` and `HowAServiceRuns`, and the screen is
`WhatToDoWithThis`. What is unread is the `lifecycle` envelope, because the app
reaches that action another way. So those four are *partly* built, and the
question they ask is narrower than the rest: not whether this app does the
thing, but whether it reads everything the wire now says about it.

**Fourteen more are partly built, the other way round:** the envelope is read and
drawn, and it answers some of the feature's requirements rather than all of
them. `A7` is the credentials a stack holds, where each stands, who made it and what uses it, and never a value (`N9-R1` to `N9-R4`); `G5` is the front door, what each address faces and why, and whether it was chosen (`N9-R9` to `N9-R11`); `G6` is which app to watch on, device by device, with its rating and what to use instead (`N9-R8`, `N9-R9`); `B1` is a stack running some of its forms, and what starting a form would come to (`N18-R1`, `N18-R3` to `N18-R9`); `A6` is what the stack keeps on the machine, secrets named and never shown (`N6-R7`); `B5` is the alert preset and its exceptions (`N10-R8`); `B10` is what
keeps running when nobody is signed in, what each command guarantees and what
did not come back (`N10-R10`, `N23-R4`); `D9` is where one item got to, followed through the services (`N8-R4` to `N8-R6`, `N8-R8`, `N8-R9`); `D5` is how full the machine is, where the room went and each download with where it stands (`N12-R1` to `N12-R3`, `N12-R6`, `N12-R10`); `D10` is the line's capacity and cap (`N10-R4` to `N10-R7`); `E2` is which version runs, how it was installed and what moving it would take (`N14-R1` to `N14-R8`); `E4`
is the record of what was changed and how far back it goes (`N11-R1` to
`N11-R3`, `N11-R9`, `N11-R10`); `E3` is the list of copies, with an empty one told apart from one that could not be read (`N6-R9`); `G8` is what leaves the machine, ours and
theirs apart (`N10-R1` to `N10-R3`, `N10-R12`). Each is kept on
[what leaves a machine](what-leaves-a-machine.md),
[what a machine keeps](what-a-machine-keeps.md),
[how full a machine is](how-full-a-machine-is.md),
[what is running here](what-is-running-here.md),
[where an item got to](where-an-item-got-to.md),
[who gets in](who-gets-in.md),
[running part of it](running-part-of-it.md),
[what a machine says](what-a-machine-says.md) or
[what was done here](what-was-done-here.md), and the rest of each feature is
still a decision nobody has made.

That leaves **nineteen** with nothing documented at all.

| Feature | What it is |
|---|---|
| `A5` | Migration from an existing stack |
| `A6` | Clean uninstall — **partly built**, see above |
| `A7` | Credential management & rotation — **partly built**, see above |
| `B1` | Forms & partial stacks — **partly built**, see above |
| `B10` | Hosting long-running commands |
| `B2` | Lifecycle control — **partly built**, see above |
| `B5` | Notifications & alerting — **partly built**, see above |
| `B8` | Autostart & boot persistence — **partly built**, see above |
| `C4` | Support bundle |
| `C7` | Queue health & stuck items |
| `D1` | Service auto-wiring |
| `D10` | Bandwidth & scheduling — **partly built**, see above |
| `D2` | Quality presets in plain language |
| `D3` | First-content walkthrough |
| `D5` | Disk space management — **partly built**, see above |
| `D6` | Household identity & invitations |
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
