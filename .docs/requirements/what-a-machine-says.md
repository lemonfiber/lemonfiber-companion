# What a machine says

The values this app reads back from a stack: verdicts, services, repairs,
releases, stalls and the household's requests. The code is the `N2` and `D7`
half of `app-modules/kernel`.

Each row says what the requirement asks and what in this repository answers it.
The spec is canonical; where this page and a requirement disagree, the
requirement is right and this page is a defect.

## Is anything wrong

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N2-R1` | The app opens on the overall verdict, and the ordering is the design: *is anything wrong*, then *what*, then *what to do* | `Verdicts` |
| `N2-R3` | A finding carries its code, its meaning and its remedy, in the core's words | `CouldNotSay` is what the core says when a check produced no verdict at all |
| `G4-R4` | Technical detail is available and does not lead | `WentWrong` — it is asked for by name rather than arriving beside everything else |
| `ARCH-R79` | Answers and their names are separated | `Ability` |

## Putting something right

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N2-R4` | The app states what else a repair affects — a blank effect is worse than a missing one | `EffectSaysNothing` |
| `N2-R5` | Confirming is not viewing: a repair is not carried out without a confirmation distinct from having read it | `Confirmed` |
| `N2-R6` | A repair confirmed against one reading is not carried out against another | `Carried` |

## Starting and stopping

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N2-R7` | Start, stop and restart by service, and a button is only offerable if the state can take it | `Daemon`, `HowAServiceRuns` |
| `N2-R8` | A disruptive action states what it disturbs, before the yes | `AgreedTo` |
| `N2-R14` | A value the contract did not carry is not substituted | `Daemons` |
| `N2-R21` | Something the machine's own configuration never declared is not presented as part of the stack | `SomethingElseRunning`, a type of its own rather than a `Daemon` with a flag |
| `N1-R27` | A state that resolves on its own is the one a stated cadence exists for | `HowAServiceRuns` |
| `B2-R15` | A service the host runs is reported as host-managed and is never started or stopped from here | `HowAServiceRuns::HostManaged`, with no control drawn beside it. The rule elsewhere is *offer the action and report the refusal*; this is the narrow case where there is no action to offer, because the control does not exist rather than being out of reach |
| `B2-R16` | An operation says what it will disturb before it acts, and names the bound rather than leaving it to be guessed | `AgreedTo`, which `Supervising::told()` is the only way past and which cannot be constructed without the thing, the verb and what the operator was shown — so rendering a listing produces none. `WhatLeansOnIt` is the bound it names, *stopping this will also stop these*, carried as a type rather than an array so the sentence cannot come out empty |

## Keeping up to date

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N2-R15` | The app holds no opinion about which of two version strings is later | `AgainstThePins`, the `update` envelope's top-level `state`: the stack compares each service with its pin and says whether any would move |
| `N2-R16` | What the decision needs, and nothing else | `Release` — a version string is an identifier rather than an ordering |
| `N2-R17` | The confirmation names the services an update would change | `KeepingCurrent` |
| `N2-R18` | Four endings rather than a boolean | `HowItEnded` |
| `N2-R19` | Undoing is not offered where the stack named neither way | `HowAServiceTookIt` |
| `N2-R20` | Applying one is not offered where the stack reported none | `TakingAnUpdate::offeredBy()`, which refuses a reading whose `AgainstThePins` is not `updates-available`, where every change was refused, or where the release carrying the pins was withdrawn |
| `N2-R22` | A change that cannot be undone is said before it is agreed to, naming the services it is true of | `TakingAnUpdate::cannotBePutBack()`, read from the wire by `Changes::permanentIn()` and drawn between the list and the buttons — an operator who has read what moves tonight and not yet agreed. Named per service rather than over the whole run: an update can move four services and be undoable for three, and a warning covering all four is refused as easily as it is believed |
| `E5-R6` | The changelog is shown in the stack-update flow, not only on a release page | `WhatAReleaseDelivers`, carried on `Release` and drawn for the release in use — whose build carries the pins an update moves onto — and on each row of the release history. The grouped notes for the running version are not drawn, and `WhatTheContractCarriesThatNothingReadsTest` records that as owed rather than unasked |
| `E5-R10` | A release with no user-facing change is stated as such rather than shown as an empty one | `WhatAReleaseDelivers::saidNothing()`, a separate arm — a row the stack said nothing about says so rather than drawing a blank where a sentence belongs |

## What has stopped coming in

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N2-R9` | Stuck downloads are reachable, and *stuck* on its own is not something anybody can act on | `Stage` |
| `N2-R10` | A log read is bounded, and a bound is only a bound if something holds it | `HowManyLines` |
| `N16-R10` | Where self-healing could not reach what it manages, that is its own answer and is never rendered as nothing needing attention | `WhatIsUnsupported`, carried on `Stalled` beside the listing rather than anywhere a screen could draw the queue without it. `Unsupported` holds both halves and refuses a blank either side, so a limit cannot reach a screen as a fault with no reason. An absent field reads as `none()` — a stack that reached everything says nothing — while a malformed one is refused, because a limit shown with half its sentence is one nobody can act on. `WhatStoppedComingIn` draws each limit, what and why, before the rows; where any is present `HowAStallReads` says the count as a count of what the stack could look at, and the screen does not say that nothing stopped |
| `N16-R13` | Queue state that could not be read is told apart from there being nothing wrong | `WhatIsStuck`, whose `met` arm is a separate answer from an empty `Stalled`. The screen draws the obstacle for the first and *nothing has stopped* only for the second, and only where the stack reached everything |
| `N16-R12` | The app does not act on a wedged item, change a strike count or a grace window, or turn the stack's own autostart on or off | `WhatStoppedComingIn` offers asking again and following an item to where it got to, and the port it reads through has no verb that could write. `WhatKeepsRunningHere` offers asking again, and hands a long-running command over or takes it back, which the requirement names as not autostart; nothing on it writes a setting |
| `N16-R14` | Nothing on this surface offers first-run setup | `WhatStoppedComingIn` offers the same two controls as under `N16-R12` and nothing else |

`N16-R8`, `N16-R9` and `N16-R11` wait on the contract rather than on this app.
They ask for why an item was classified as wedged and how many strikes it
holds, for removing, blocklisting and re-searching as three acts, and for a
rehearsed self-heal labelled as one. The `stuck` envelope carries a title, a
service and a stage per item, and no envelope carries a strike, a
classification or a self-heal at all. `N1-R17` is why that is written down here
rather than approximated from the stage.

## What keeps running when nobody is signed in

These are lemonfiber's own long-running commands (`B10`), which the `hosting`
envelope carries and `WhatKeepsRunningHere` draws.

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N10-R10` | A long-running command is shown with what it guarantees and what is missing, and one that is defined but not running is shown as that rather than as absent | `HowItIsHosted`, six standings each with a word of its own, so *installed and not running* cannot be drawn as *not hosted*. `didNotComeBack()` counts `Orphaned` as well as `Stopped` — the definition is installed and the program it names is gone, so it cannot run — and does not count `NotHosted`, where nothing was installed and so nothing failed to start |
| `N10-R10` | A standing the service manager declined to confirm is not drawn as one it confirmed | `HowItIsHosted::InstalledUnverified`. It answers none of the three questions, so a screen cannot draw it from a boolean and has to have a sentence for it — which is what `EveryDerivedKeyResolvesTest` then requires of both locales |
| `N10-R10` | What did not come back is named | `WhatRunsUnattended::didNotComeBack()`, answering `WhatDidNotComeBack` — a type of its own rather than a filtered array (`D1`), because *everything this machine hosts* and *everything that did not start* are different lists for different moments. What counts is `HowItIsHosted`'s, so the walk does not judge |
| `N10-R10` | For an orphan, what is missing is the program rather than the service | `Unattended::orphaned()`, which carries the missing path and sets the standing itself — a row naming a missing program while claiming to be running cannot be built. `Unattended::missing()` hands it over in two arms, so a screen cannot print a blank where a path belongs |
| `N23-R4` | Where the platform has no service manager lemonfiber configures, the stack's instruction is shown and no install is offered | `WhatRunsUnattended::unsupported()`, which takes the instruction and refuses a blank one through `InstructionSaysNothing`. It is a named constructor rather than a nullable parameter, so an unsupported machine with no instruction and an instruction attached to a machine that has a manager are both unspellable. `WhatKeepsItRunning::configuresAnything()` is the same line drawn about the machine, and `WhatKeepsRunningTurnedOutToBe::$handsOver` is false there, so the screen draws neither install nor removal and `wouldInstall()` asks nothing |
| `N23-R1` | Hosting a named long-running command and removing one are each offered as an act of their own, and nothing else hosts a command | `Hosting::handOver()`, which takes a `HostingAgreed` naming one act and one command. `WhatKeepsRunningHere` builds it only from a command the listing carries, holds it as a question, and sends it only from `agree()`; no other screen or port reaches `hosting-install` or `hosting-remove`. `Keepers` sends the command's name as `kept` under a fresh idempotency key |
| `N23-R2` | After an install, whether the command was started and where its words are written are said, and the standing the stack returned is reported rather than the install having succeeded | `WhatTheHandoverDid`, read by `Handovers` from `hosting.changed` and from the acted-on command's own row: `started`, `output` and `standing`, each required or refused, and `output` absent or null read as the stack not saying. The screen's heading names what was asked for; whether it runs is the standing line under it |
| `N23-R3` | Every file an install or a removal wrote or took back is shown with its result | `TheFilesTouched`, from `changed.touched`, drawn one line a file as *written* or *taken back* by which act it was, and as *would be* on a rehearsal. An empty list is its own sentence, so removing what was never hosted reads as nothing to take back rather than as a failure |
| `N23-R5` | A rehearsed install or removal is labelled as a rehearsal | `WhatTheHandoverDid::wasRehearsed()`, from `changed.rehearsed`, which `Handovers` requires rather than defaulting to false. The screen says it first, above everything the rehearsal lists |

The guard on the data location is started against forms, and the stack refuses
to install it against none. Nothing this app reads says which command takes
forms: the `hosting` envelope lists each command's name, standing and
guarantee, and says nothing of what installing it needs. So the app sends the
command's name alone, and an install of the guard reaches the operator as the
stack's own refusal, in its words, through `HowTheHandoverWent::refused()`.
`N1-R17` is why it is not guessed from the command's name.

`N16-R5` to `N16-R7` are not answered here. They are about the stack's own
autostart (`B8`) — whether it is configured, and what a restart did and did not
bring back — and no envelope reports it. `hosting` is a different subject, and
`N1-R17` is why its standings are not offered as an answer to these.

## What a machine is set to, and who put things there

| Requirement | What it asks | What keeps it |
|---|---|---|
| `F7-R3` | Wherever a setting, wiring or check is shown, its origin — bundled, operator, or a named plugin — is shown beside it | `WhoPutItThere`, one type for every surface because the core publishes one origin type for all of them. A setting carries it on `Setting` and every settings row draws it; a check carries it as a required argument of `Finding::of()`, and the report marks every check that is not the stack's own beside its title, saying once under the list what an unmarked row is. `HowAnOriginReads` is the one fold and `CameFrom` the one component, each screen choosing only its sentence off `WhoSetIt` |
| `C1-R15` | A proof a plugin declares runs as a check like any other and is attributed to the plugin | The same `Finding`, the same verdicts and the same screen as any other check; the attribution is the check's origin, read by `Reports` and drawn beside the title |
| `F7-R4` | A value a plugin overrode shows both the value in force and the value it replaced | `WhoPutItThere::overridden()` carries a `WhatItReplaced`: the value it held, that nothing was set, or that a credential is withheld, each with where that value came from. The settings screen draws both lines under the setting, and a withheld credential stays sealed |
| `F7-R10` | A value left in force by a removed plugin is reported as orphaned, naming the plugin that set it | `WhoPutItThere::orphaned()`, an arm of its own that requires the plugin's name. Every surface that draws an origin draws it in its own sentence |
| `F7-R11` | An origin that cannot be determined is reported as unknown and never as bundled | `WhoPutItThere::unknown()`, a separate arm carrying the stack's reason and refusing a blank one. `Attributions`, the one reader every attributing envelope shares, refuses an unreadable origin rather than defaulting it, and turns a blank name into its own refusal so each envelope's adapter catches it. `AnOriginIsUnnamed` keeps *unknown* from becoming the arm this app's own read failures land in; an unknown check or service is always marked |
| `F7-R12` | Plugins do not get parallel surfaces of their own; they appear on the existing ones | A plugin-set value is a row on the settings screen with a different origin, not a screen of its own |

## What the household asked for

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N2-R11` | A waiting request is approvable and refusable from the app | `Decided` |
| `N2-R11` | Requests awaiting a decision are surfaced, and one row nobody can label does not take the rest with it | `HowARequestStands`, a two-arm union carried on `Wanted` in place of the bare enum. The contract leaves `state` out where the request service reported a status lemonfiber has no word for, and `WhatWasAskedFor::standing()` reads that absence as `unnamed()` rather than refusing the household. `wantsADecision()` answers false for it, so nothing offers to approve a status the stack declined to name; a standing that arrives spelled out and unrecognised is still refused, because that is drift between the contract and the enum rather than a fact about the household |
| `D7-R3` | A size is shown before a request is approved, and *how many bytes* becomes words somewhere | `HowBig` |
| `D7-R4` | An estimate is labelled as one, because most of these are estimates | `HowBig` |
| `D7-R7` | The reason is part of declining rather than something beside it | `Decided`, which has no arm that takes a nullable reason |
| `N3-R7` | A refused request carries the reason that was given | `TurnedDown` |
