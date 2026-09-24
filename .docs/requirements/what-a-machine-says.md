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
| `N2-R15` | The app holds no opinion about which of two version strings is later | `HowCurrent` |
| `N2-R16` | What the decision needs, and nothing else | `Release` — a version string is an identifier rather than an ordering |
| `N2-R17` | The confirmation names the services an update would change | `KeepingCurrent` |
| `N2-R18` | Four endings rather than a boolean | `HowItEnded` |
| `N2-R19` | Undoing is not offered where the stack named neither way | `HowAServiceTookIt` |
| `N2-R20` | Applying one is not offered where the stack reported none | `HowCurrent` |
| `N2-R22` | A change that cannot be undone is said before it is agreed to, naming the services it is true of | `TakingAnUpdate::cannotBePutBack()`, read from the wire by `Changes::permanentIn()` and drawn between the list and the buttons — an operator who has read what moves tonight and not yet agreed. Named per service rather than over the whole run: an update can move four services and be undoable for three, and a warning covering all four is refused as easily as it is believed |
| `E5-R6` | The changelog is shown in the stack-update flow, not only on a release page | `WhatAReleaseDelivers`, carried on `Release` and drawn on the row the *take this one* control sits on. The grouped notes for the running version are not drawn, and `WhatTheContractCarriesThatNothingReadsTest` records that as owed rather than unasked |
| `E5-R10` | A release with no user-facing change is stated as such rather than shown as an empty one | `WhatAReleaseDelivers::saidNothing()`, a separate arm — a row the stack said nothing about says so rather than drawing a blank where a sentence belongs |

## What has stopped coming in

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N2-R9` | Stuck downloads are reachable, and *stuck* on its own is not something anybody can act on | `Stage` |
| `N2-R10` | A log read is bounded, and a bound is only a bound if something holds it | `HowManyLines` |
| `N16-R10` | Where self-healing could not reach what it manages, that is its own answer and is never rendered as nothing needing attention | `WhatIsUnsupported`, carried on `Stalled` beside the listing rather than anywhere a screen could draw the queue without it. `Unsupported` holds both halves and refuses a blank either side, so a limit cannot reach a screen as a fault with no reason. An absent field reads as `none()` — a stack that reached everything says nothing — while a malformed one is refused, because a limit shown with half its sentence is one nobody can act on |

## What keeps running when nobody is signed in

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N16-R5` | Whether the stack comes back after a restart is shown, and where the platform cannot provide it the app says so rather than rendering it as off | `HowItIsHosted`, whose `cannotBePromisedHere()` is a separate question from `comesBackOnItsOwn()` so that *not available here* cannot be drawn as *off*; and `WhatKeepsItRunning::configuresAnything()`, the same line drawn about the machine rather than about one command |
| `N16-R6` | A restart that did not bring everything back names what did not come back, and is not reported as a completed start | `HowItIsHosted::didNotComeBack()`, which counts `Orphaned` as well as `Stopped` — the definition is installed and the program it names is gone, so it cannot run — and does not count `NotHosted`, where nothing was installed and so nothing failed to start |
| `N16-R13` | State that could not be read is told apart from there being nothing wrong | `HowItIsHosted::InstalledUnverified`, the manager declining to say. It answers none of the three questions, so a screen cannot draw it from a boolean and has to have a sentence for it — which is what `EveryDerivedKeyResolvesTest` then requires of both locales |
| `N16-R5` | The sentence that makes *not available here* read differently from *off* | `WhatRunsUnattended::unsupported()`, which takes the instruction and refuses a blank one through `InstructionSaysNothing`. It is a named constructor rather than a nullable parameter, so an unsupported machine with no instruction and an instruction attached to a machine that has a manager are both unspellable |
| `N16-R6` | What did not come back is named | `WhatRunsUnattended::didNotComeBack()`, answering `WhatDidNotComeBack` — a type of its own rather than a filtered array (`D1`), because *everything this machine hosts* and *everything that did not start* are different lists for different moments. What counts is `HowItIsHosted`'s, so the walk does not judge |
| `N16-R6` | For an orphan, the honest name is the program rather than the service | `Unattended::orphaned()`, which carries the missing path and sets the standing itself — a row naming a missing program while claiming to be running cannot be built. `Unattended::missing()` hands it over in two arms, so a screen cannot print a blank where a path belongs |

`N16-R7` is deliberately absent. It asks for post-boot verification *with when
it last ran*, and no envelope carries a time for one — `installed-unverified`
is the service manager declining to confirm, which is a different subject. The
spec's own notes on `N16` say so, and `N1-R17` is why the difference is written
down here rather than approximated from the nearest field.

## What a machine is set to, and who put things there

| Requirement | What it asks | What keeps it |
|---|---|---|
| `F7-R3` | Wherever a setting, wiring or check is shown, its origin — bundled, operator, or a named plugin — is shown beside it | `WhoPutItThere`, one type for every surface because the core publishes one origin type for all of them. A setting carries it on `Setting` and every settings row draws it; a check carries it as a required argument of `Finding::of()`, and the report marks every check that is not the stack's own beside its title, saying once under the list what an unmarked row is. `HowAnOriginReads` is the one fold and `CameFrom` the one component, each screen choosing only its sentence off `WhoSetIt` |
| `C1-R15` | A proof a plugin declares runs as a check like any other and is attributed to the plugin | The same `Finding`, the same verdicts and the same screen as any other check; the attribution is the check's origin, read by `Reports` and drawn beside the title |
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
