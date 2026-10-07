# What a machine says

The values this app reads back from a stack: verdicts, services, repairs,
releases, stalls and the household's requests. The code is the `N2` and `D7`
half of `app-modules/kernel`.

Each row says what the requirement asks and why it is answered the way it is;
whether it is kept, and by what, is its row in `status.toml`.
The spec is canonical; where this page and a requirement disagree, the
requirement is right and this page is a defect.

## Is anything wrong

| Requirement | What it asks | Why |
|---|---|---|
| `N2-R1` | The app opens on the overall verdict, and the ordering is the design: *is anything wrong*, then *what*, then *what to do* | `Standings`, the word each stack's one line last said, which the list of stacks opens on. [the-one-line.md](the-one-line.md) has how it is kept |
| `N2-R3` | A finding carries its meaning and its remedy, in the core's words, and its code one step away | `CouldNotSay` is what the core says when a check produced no verdict at all |
| `G4-R4` | Technical detail is available and does not lead | It is asked for by name rather than arriving beside everything else |
| `G4-R7` | Where no remedy is known, the app says so and offers escalation | A card naming no remedy says *The stack did not suggest anything to try for this*, and `SomebodyToAsk` offers *Send a report to someone helping you* once, at the foot of the summary's items and of the findings, wherever any card said it; it opens the help screen, whose report covers every card at once |
| `ARCH-R79` | Answers and their names are separated | |

## Getting underneath it

What the app shows of how the stack does its work, what the operator chose
over lemonfiber's own, and what can be done at the machine instead of here.

| Requirement | What it asks | Why |
|---|---|---|
| `N19-R1` | Where the app offers an action, it can show the command that performs it | After a start, a stop or a restart: `Lifecycles` reads `command` into `TheCommandLine`, which `WhatTheVerbCameTo::whetherItRan()` hands only to a verb that ran, and the outcome card draws it verbatim at its foot under "The command it ran", or "The command it will run" for a rehearsal, the words joined by spaces as the stack's terminal prints them. A start the stack declined ran nothing and shows none. Before the yes of a verb that asks first (a stop, a restart or a fetch), `ShowsWhatTheYesWillRun` asks the stack to rehearse that verb (`Supervising::rehearsed()`, the action with `dry_run`, under no key) and draws the command its rehearsal reports under "The command it will run" above the yes; nothing is drawn until the stack has said, and never a command from a report that was not a rehearsal. The other actions this app offers answer with no command, rehearsed or carried out, which `WhatTheContractDoesNotCarryTest` holds |
| `N19-R2` | An action available outside the app is offered plainly, never under a caution that discourages taking it | Updating lemonfiber at the machine on `WhatIsRunningHere`, setting up what survives a restart and handing a command to the service manager on `WhatKeepsRunningHere`, and the guard's way to hosting are said in plain sentences, and `AWayAroundTheAppIsOfferedPlainlyTest` holds each of those lines, in both languages, to none of the phrases a discouragement is made of. A copy another tool keeps up to date is drawn in the tone of a copy that is fine, and the update command is drawn verbatim straight under the line that offers it, in no notice |
| `N19-R3` | A stack file the operator edited is shown as edited and kept, never as drift | `AStackEdit`, one shape for every edit the stack reports: a file a reset reverts, and a file a start or an update leaves as the operator set it. `StackEditsSent` reads `stack_edits` off the `lifecycle` and `update` envelopes into `WhatTheVerbCameTo::editsKept()` and `Upkeep::editsKept()`, and the `edits-kept` component draws each with what lemonfiber would change in it and a legend for the marks, with no tone and nothing offered |
| `N19-R4` | The app never offers to reassert a value the operator changed | No port the kernel declares takes a stack file the operator edited or a connection of theirs, so nothing can put one back on its own. An edited file is drawn as kept with nothing offered: a verb's outcome and the update screen offer what they would with no edit at all, and the wiring draws a changed or conflicted connection with both values and nothing to put it back. Putting lemonfiber's own back over the operator's is the reset alone, the explicit and confirmed action `C9-R12` asks for: a road of its own from the settings screen, previewed first, and carried out only on `AResetAgreed`, which can be built only from a preview that would revert something. `ResettingTheConfiguration::revert()` takes nothing else |
| `N19-R6` | The app writes nothing to an area declared unmanaged, and reports no drift for one | The stack leaves what the operator declared unmanaged alone, and the app draws each place it says so as that. A connection the wiring `observed` is *left alone, because you said to*, with the operator's reason and neither value, never as changed, failed or skipped, and with nothing offered on it. A repair that came to `WhatBecameOfIt::Unmanaged` changed nothing and is not offered again. A change to a setting the stack refused because the area is declared unmanaged is drawn in the stack's words, which carry the reason the operator gave, and offers no yes: `Stance::canBeAgreedTo()` is true only of a staged change, and a refused one is refused again when confirmed |
| `N19-R9` | Commands, edited files or unmanaged areas that could not be read are told apart from there being none | A verb's report whose command or edited files do not read is refused whole, as `LifecycleIsUnreadable`, `StackEditsAreUnreadable` or `AStackEditCannotBeShown`, and drawn as what stood in the way rather than as a verb that ran nothing or kept no file; a list of edits that is absent is refused rather than read as empty. Before a yes, a rehearsal that could not be read is said as *The stack could not say which command it will run*, apart from a rehearsal saying the verb would run nothing, which draws no line. A wiring run that could not judge whether anything was changed by hand says so before its connections. The declared areas are not read at all, which `WhatTheContractDoesNotCarryTest` holds under `N19-R5` |
| `N19-R10` | Nothing on this surface offers first-run setup | The settings screen says first-run setup is not done from this app and why, and offers no way to it. The hosting screen points a machine keeping nothing running to the machine and offers asking again and the guard, and the wiring and version screens offer their own controls and nothing else. No verb this app can ask a stack for is setup |

`N19-R5`, `N19-R7` and `N19-R8` wait on the contract rather than on this app,
and so does `N19-R1` for every action but a start, a stop and a restart. No
envelope lists the areas the operator declared unmanaged other than as the raw
value of their setting, which this app does not parse; none says the stack runs
without lemonfiber or that every action has a non-interactive equivalent; none
says whether the stack is the operator's own; and no action but those three
answers with the command it runs. `WhatTheContractDoesNotCarryTest` holds a row
for each, and `N1-R17` is why none is worked out here.

## Putting something right

| Requirement | What it asks | Why |
|---|---|---|
| `N2-R4` | The app states what else a repair affects — a blank effect is worse than a missing one | |
| `N2-R5` | Confirming is not viewing: a repair is not carried out without a confirmation distinct from having read it | |
| `N2-R6` | A repair confirmed against one reading is not carried out against another, and is offered again | The stack refuses a yes whose offer has moved as `REPAIR-1`; `Menders` reads that as `HowTheRepairIsGoing::moved()` in the stack's words, and `WhatWouldBePutRight` lets go of the yes, asks for the offer again and draws the stack's words above it |

## Starting and stopping

| Requirement | What it asks | Why |
|---|---|---|
| `N2-R7` | Start, stop and restart by service, and a button is only offerable if the state can take it | |
| `N2-R8` | A disruptive action states what it disturbs, before the yes | |
| `N2-R14` | A value the contract did not carry is not substituted | |
| `N2-R21` | Something the machine's own configuration never declared is not presented as part of the stack | `SomethingElseRunning`, a type of its own rather than a `Daemon` with a flag |
| `N1-R27` | A state that resolves on its own is the one a declared cadence exists for | |
| `B2-R15` | A service the host runs is reported as host-managed and is never started or stopped from here | `HowAServiceRuns::HostManaged`, with no control drawn beside it. The rule elsewhere is *offer the action and report the refusal*; this is the narrow case where there is no action to offer, because the control does not exist rather than being out of reach |
| `N16-R6` | A restart that did not bring everything back names what did not come back, and is not reported as a completed start | `WhatTheVerbCameTo::broughtEverythingBack()`, true only where the stack called the services `active` and named none of them short of up, so a report that leaves the condition out is not a completed start either. `whatDidNotComeBack()` asks `HowAServiceRuns::cameBack()` of each service the stack waited for: back is running or healthy, and a service the host runs is not this stack's to bring back. `Lifecycles` reads the report a verb's handle is redeemed for, and `HowAVerbEndedReads` says *it did not bring everything back* and names each service with where it stood. A start the stack declined is `WhatTheVerbCameTo::declined()`, which refuses a blank reason, and `whetherItRan()` hands the reason to its own arm, drawn rather than said as nothing |
| `N16-R5` | Whether the stack comes back after a restart is shown, and where the machine cannot be set to bring it back, the app says what to do instead rather than showing it as off | `WhatRunsUnattended` holds each command, what it guarantees and where it stands, and refuses a machine this product cannot configure that says nothing to do instead; `WhatKeepsItRunning` says which managers this product sets up; `Hosts` reads the listing into them, and `WhatKeepsRunningHere` draws it |
| `N6-R1` | A rehearsed verb is labelled as one and not described as having happened | `WhatTheVerbCameTo::was()`, which `Lifecycles` requires rather than defaults. `HowAVerbEndedReads` heads the report as a rehearsal, words what was left out as what *would be*, and names nothing as not having come back, because nothing was brought up |
| `N18-R3` | A service a verb's plan filtered out is shown as filtered, with its reason | `WhatTheVerbCameTo::leftOut()`, read from `plan.filtered` by the same `WhatWasLeftOut` the listing and the rehearsal read theirs with |
| `N7-R5` | A port a verb wanted that something else holds names what holds it | `WhatTheVerbCameTo::portsHeld()`, read from `port_conflicts` into the `APortHeld` the survey uses: the port, the service that wants it and the project holding it |
| `N1-R27` | A verb sent is followed on a declared cadence until the stack reports | `WhatToDoWithThis::whileItSettles()` asks again while `FollowsWhatTheVerbCameTo` says the verb is running. `Supervisors::whatBecameOf()` reads `NoSuchJob` as ended, which the screen says as no outcome rather than as a failure |
| `B2-R16` | An operation says what it will disturb before it acts, and names the bound rather than leaving it to be guessed | `AgreedTo`, which `Supervising::told()` is the only way past and which cannot be constructed without the thing, the verb and what the operator was shown — so rendering a listing produces none. `WhatLeansOnIt` is the bound it names, *stopping this will also stop these*, carried as a type rather than an array so the sentence cannot come out empty |

## Keeping up to date

| Requirement | What it asks | Why |
|---|---|---|
| `N2-R15` | The app holds no opinion about which of two version strings is later | `AgainstThePins`, the `update` envelope's top-level `state`: the stack compares each service with its pin and says whether any would move |
| `N2-R16` | What the decision needs, and nothing else | A version string is an identifier rather than an ordering |
| `N2-R17` | The confirmation names the services an update would change | |
| `N2-R18` | Four endings rather than a boolean | |
| `N2-R19` | Undoing is not offered where the stack named neither way | |
| `N2-R20` | Applying one is not offered where the stack reported none | `TakingAnUpdate::offeredBy()`, which refuses a reading whose `AgainstThePins` is not `updates-available`, where every change was refused, or where the release carrying the pins was withdrawn |
| `N2-R22` | A change that cannot be undone is said before it is agreed to, naming the services it is true of | `TakingAnUpdate::cannotBePutBack()`, read from the wire by `Changes::permanentIn()` and drawn between the list and the buttons — an operator who has read what moves tonight and not yet agreed. Named per service rather than over the whole run: an update can move four services and be undoable for three, and a warning covering all four is refused as easily as it is believed |
| `E5-R6` | The changelog is shown in the stack-update flow, not only on a release page | `WhatAReleaseDelivers`, carried on `Release` and drawn for the release in use — whose build carries the pins an update moves onto — and on each row of the release history. The grouped notes for the running version are not drawn, and `WhatTheContractCarriesThatNothingReadsTest` records that as owed rather than unasked |
| `E5-R10` | A release with no user-facing change is stated as such rather than shown as an empty one | `WhatAReleaseDelivers::saidNothing()`, a separate arm — a row the stack said nothing about says so rather than drawing a blank where a sentence belongs |

## What has stopped coming in

| Requirement | What it asks | Why |
|---|---|---|
| `N2-R9` | Stuck downloads are reachable, and *stuck* on its own is not something anybody can act on | |
| `N2-R10` | A log read is bounded, and a bound is only a bound if something holds it | |
| `N16-R10` | Where self-healing could not reach what it manages, that is its own answer and is never rendered as nothing needing attention | `WhatIsUnsupported`, carried on `Stalled` beside the listing rather than anywhere a screen could draw the queue without it. `Unsupported` holds both halves and refuses a blank either side, so a limit cannot reach a screen as a fault with no reason. An absent field reads as `none()` — a stack that reached everything says nothing — while a malformed one is refused, because a limit shown with half its sentence is one nobody can act on. `WhatStoppedComingIn` draws each limit, what and why, before the rows; where any is present `HowAStallReads` says the count as a count of what the stack could look at, and the screen does not say that nothing stopped |
| `N16-R13` | Queue state that could not be read is told apart from there being nothing wrong | `WhatIsStuck`, whose `met` arm is a separate answer from an empty `Stalled`. The screen draws the obstacle for the first and *nothing has stopped* only for the second, and only where the stack reached everything |
| `N16-R12` | The app does not act on a wedged item, change a strike count or a grace window, or turn the stack's own autostart on or off | `WhatStoppedComingIn` offers asking again and following an item to where it got to, and the port it reads through has no verb that could write. `WhatKeepsRunningHere` offers asking again, and hands a long-running command over or takes it back, which the requirement names as not autostart; nothing on it writes a setting |
| `N16-R14` | Nothing on this surface offers first-run setup | `WhatStoppedComingIn` offers the same two controls as under `N16-R12` and nothing else |
| `N23-R6` | A stopped item is shown in the category the stack gave it, in the stack's order, and *slow* is not rendered as stuck | `HowItStopped`, the seven kinds the dashboard's `stuck` rows carry, read by `StoppedRows` and refused where the word is one this app does not read. `WhatStoppedMoving` keeps the stack's order and nothing re-ranks it. `HowTheOneLineReads` puts the kinds that want a fix under *stopped in the queue* and *slow* under its own heading after them, saying it needs time rather than a fix |
| `N23-R7` | One cause standing for several items is shown once with the count, never as a row per item | `AStoppage` holds the row the stack folded, its name the shared cause and `items` how many it stands for; the health screen draws it once with the count. A row standing for none is refused |
| `N23-R8` | What a service said was blocking an item is shown in its own words, with how long it has been that way | `AStoppage::blocking()` carries the service's words as they came, empty where it said nothing, and `heldFor()` the seconds the stack counted; the screen says both, the span in `HowLongAgo`'s bands so it reads like every other length of time here |
| `N23-R9` | A queue that could not be read, or one lemonfiber cannot read, is said, naming each service and its reason, and the list is not presented as complete | `Stalled` carries `incomplete` as `HowMuchIsShown` and `unsupported` as `WhatIsUnsupported`. `WhatStoppedComingIn` says how much is shown on every reading and draws each service it could not reach with why, before the rows, as under `N16-R10` |
| `N23-R10` | A stuck item leads to its trace, and not only to a count | `WhatStoppedComingIn::traceOf()` on every stalled item, and `HowThisStackIs::traceOf()` on every stopped row that stands for one item. A row standing for several names a cause, which a trace cannot follow, so it offers none |

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

| Requirement | What it asks | Why |
|---|---|---|
| `N10-R10` | A long-running command is shown with what it guarantees and what is missing, and one that is defined but not running is shown as that rather than as absent | `HowItIsHosted`, six standings each with a word of its own, so *installed and not running* cannot be drawn as *not hosted*. `didNotComeBack()` counts `Orphaned` as well as `Stopped` — the definition is installed and the program it names is gone, so it cannot run — and does not count `NotHosted`, where nothing was installed and so nothing failed to start |
| `N10-R10` | A standing the service manager declined to confirm is not drawn as one it confirmed | `HowItIsHosted::InstalledUnverified`. It answers none of the three questions, so a screen cannot draw it from a boolean and has to have a sentence for it — which is what `EveryDerivedKeyResolvesTest` then requires of both locales |
| `N10-R10` | What did not come back is named | `WhatRunsUnattended::didNotComeBack()`, answering `WhatDidNotComeBack` — a type of its own rather than a filtered array, because *everything this machine hosts* and *everything that did not start* are different lists for different moments. What counts is `HowItIsHosted`'s, so the walk does not judge |
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

A guard can also be started from this app without handing it to anything. It
is a job the stack keeps only while somebody asks about it, drawn by
`GuardingWhileYouWatch` on a screen of its own, reached from
`WhatKeepsRunningHere` under a line saying it is not hosting.

| Requirement | What it asks | Why |
|---|---|---|
| `N23-R11` | Starting the data-root guard for forms the operator names is offered as an act of its own, with what it would guard and do said before it starts; a refusal to start is shown with the stack's reason | `Guarding::guard()`, which takes an `AGuardAskedFor` naming the forms and is sent by `Guards` as the `watch` action under a fresh idempotency key. `GuardingWhileYouWatch` offers each form the stack declares, holds the guard as a question naming the forms it would stop, and sends it only from `agree()`. Before it starts the screen says what a guard does and that the stack does not say beforehand which location it would guard, how often it looks or the command it would run: `watch.would` is answered only by a rehearsal the web route cannot ask for, recorded in `WhatTheContractDoesNotCarryTest`. A guard that could not start is `HowTheGuardIsGoing::refused()`, carrying the stack's sentence from the error or the prose it answered with |
| `N23-R12` | The app says plainly, before the guard starts and while it runs, that it lives only while this screen asks and stops when it is left; it asks on the cadence it declares, releases the guard when the screen is left, and never presents it as hosted | The screen says both before the guard starts and while it runs. `whileItGuards()` asks after the guard on `HowOftenAScreenLooks::WhileWorkRuns`, and only while it guards; each asking is what keeps it. `unmount()` lets it go through `Guarding::letGo()`, which `Guards` sends as `DELETE /api/jobs/{job}`, unless it has already ended. It is a screen of its own apart from the hosted rows, and it points to hosting for a guard that outlives the screen |
| `N23-R13` | A guard that saw the data location go is shown with the forms it stopped, whether stopping them worked, and why it ended, and one that did not succeed is never shown as having stopped them; a guard ended without an outcome and one the stack no longer knows are each told apart from one that saw it go and one still guarding | `HowTheGuardIsGoing`, six arms. `Vigils` reads `watch.forms`, `watch.stopped` and `watch.reason`, each required, into `WhatTheGuardSaw`, and `HowAGuardReads` says *stopped* and *could not stop* in sentences of their own. `job` at `200` is `ended()`, and a name the stack's run never handed out is `unknown()` rather than ended. A guard another client released and one the stack let go both answer `job` at `200`, recorded in `WhatTheContractDoesNotCarryTest`, so the screen says it was released or not asked about without saying which |

`N16-R5` and `N16-R7` are not answered here. They are about the stack's own
autostart (`B8`) — whether it is configured, and whether a check after a boot
ran — and no envelope reports either. `N16-R6` is answered under *Starting and
stopping* for a start or a restart sent from this app; a start the machine made
on its own at a boot is not one this app holds a handle for. `hosting` is a
different subject, and `N1-R17` is why its standings are not offered as an
answer to these.

## What a machine is set to, and who put things there

| Requirement | What it asks | Why |
|---|---|---|
| `F7-R3` | Wherever a setting, wiring or check is shown, its origin — bundled, operator, or a named plugin — is shown beside it | `WhoPutItThere`, one type for every surface because the core publishes one origin type for all of them. A setting carries it on `Setting` and every settings row draws it; a check carries it as a required argument of `Finding::of()`, and the report marks every check that is not the stack's own beside its title, saying once under the list what an unmarked row is. `HowAnOriginReads` is the one fold and `CameFrom` the one component, each screen choosing only its sentence off `WhoSetIt` |
| `C1-R15` | A proof a plugin declares runs as a check like any other and is attributed to the plugin | The same `Finding`, the same verdicts and the same screen as any other check; the attribution is the check's origin, read by `Reports` and drawn beside the title |
| `F7-R4` | A value a plugin overrode shows both the value in force and the value it replaced | `WhoPutItThere::overridden()` carries a `WhatItReplaced`: the value it held, that nothing was set, or that a credential is withheld, each with where that value came from. The settings screen draws both lines under the setting, and a withheld credential stays sealed |
| `F7-R10` | A value left in force by a removed plugin is reported as orphaned, naming the plugin that set it | `WhoPutItThere::orphaned()`, an arm of its own that requires the plugin's name. Every surface that draws an origin draws it in its own sentence |
| `F7-R11` | An origin that cannot be determined is reported as unknown and never as bundled | `WhoPutItThere::unknown()`, a separate arm carrying the stack's reason and refusing a blank one. `Attributions`, the one reader every attributing envelope shares, refuses an unreadable origin rather than defaulting it, and turns a blank name into its own refusal so each envelope's adapter catches it. `AnOriginIsUnnamed` keeps *unknown* from becoming the arm this app's own read failures land in; an unknown check or service is always marked |
| `F7-R12` | Plugins do not get parallel surfaces of their own; they appear on the existing ones | A plugin-set value is a row on the settings screen with a different origin, not a screen of its own |

## What the household asked for

| Requirement | What it asks | Why |
|---|---|---|
| `N2-R11` | A waiting request is approvable and refusable from the app | |
| `N2-R11` | Requests awaiting a decision are surfaced, and one row nobody can label does not take the rest with it | `HowARequestStands`, a two-arm union carried on `Wanted` in place of the bare enum. The contract leaves `state` out where the request service reported a status lemonfiber has no word for, and `WhatWasAskedFor::standing()` reads that absence as `unnamed()` rather than refusing the household. `wantsADecision()` answers false for it, so nothing offers to approve a status the stack declined to name; a standing that arrives spelled out and unrecognised is still refused, because that is drift between the contract and the enum rather than a fact about the household |
| `D7-R3` | A size is shown before a request is approved, and *how many bytes* becomes words somewhere | |
| `D7-R4` | An estimate is labelled as one, because most of these are estimates | |
| `D7-R7` | The reason is part of declining rather than something beside it | `Decided`, which has no arm that takes a nullable reason |
| `N3-R7` | A refused request carries the reason that was given | |
