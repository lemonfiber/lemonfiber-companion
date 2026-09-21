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

## What has stopped coming in

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N2-R9` | Stuck downloads are reachable, and *stuck* on its own is not something anybody can act on | `Stage` |
| `N2-R10` | A log read is bounded, and a bound is only a bound if something holds it | `HowManyLines` |

## What the household asked for

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N2-R11` | A waiting request is approvable and refusable from the app | `Decided` |
| `D7-R3` | A size is shown before a request is approved, and *how many bytes* becomes words somewhere | `HowBig` |
| `D7-R4` | An estimate is labelled as one, because most of these are estimates | `HowBig` |
| `D7-R7` | The reason is part of declining rather than something beside it | `Decided`, which has no arm that takes a nullable reason |
| `N3-R7` | A refused request carries the reason that was given | `TurnedDown` |
