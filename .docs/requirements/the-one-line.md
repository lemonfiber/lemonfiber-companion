# The one line

The health summary every surface says about a stack, and how this app holds it.
The core computes it once, into the dashboard, and publishes that only on its
event stream, so the line is read off the stream rather than asked for. The code
is `TheHealthSummary` and the `Hearing` and `Standings` ports in
`app-modules/kernel`, `Summaries` and `Listeners` in `app-modules/sdk`,
`WhatWasHeardSoFar` in `app-modules/health`, `PlatformStandings` in
`app-modules/vault`, and `HearsHowTheStackIs` on `HowThisStackIs` and
`YourStacks` in `app-modules/operator`.

Each row says what the requirement asks and what in this repository answers it.
The spec is canonical; where this page and a requirement disagree, the
requirement is right and this page is a defect.

## What the line says

| Requirement | What it asks | What keeps it |
|---|---|---|
| `G7-R1` | A single-line health summary is available on every surface | The first thing `HowThisStackIs` draws, above the findings, and the line under each stack's name on `YourStacks` |
| `G7-R8` | The summary is identical across surfaces, from one computation | `Summaries` reads the `health` of the `dashboard` envelope and nothing else, and no class here computes a word, a count or a worst thing. The list says the word `HowThisStackIs` last heard, kept in `Standings`, and `HowTheOneLineReads` renders it on both screens in the same sentences |
| `G7-R5` | Where health cannot be determined, the summary reads unknown and never healthy | `HowItStands::Unknown` has its own sentence. A summary that is no longer current is drawn as unknown whatever it said, and a stream that could not be heard before it said anything is drawn as unknown with what stopped it. On the list, a stack never heard reads unknown, and so does a kept word older than `WhatWasHeardSoFar::isStillCurrent()` allows |
| `G7-R7` | The summary expands to the affected items and their remedies | The count is a control, offered only where the core counted something. Opened out, every item shows its severity, its check, what is wrong, what it costs, what to try and what else is wrong because of it |
| `G7-R9` | A deliberately stopped stack reports `stopped`, not a failure | `HowItStands::Stopped`, drawn as a sentence saying that is not a fault |
| `G7-R10` | Failures confined to non-essential services report as advisory, not as requiring attention | `HowItStands::Advisory` counts in notes. Only `degraded`, `broken` and `critical` count things needing attention |
| `G7-R12` | During startup the summary reports `unknown` rather than a failure | The core sends `unknown` for a stack that is starting, and the app draws the word it was sent |
| `G7-R13` | A healthy summary is as clearly presented as an unhealthy one | Each of the eight words is its own sentence, in the same emphasis |

## How the line is held

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N1-R67` | A screen may hold a subscription in place of reading, and opening it is that screen's one read. What arrives is rendered from what the subscription holds | `Hearing`, bound fresh for each screen. `HowThisStackIs` holds one `Listeners` and draws from `WhatWasHeardSoFar`, which is a value on the screen |
| `N1-R68` | The first frame is published before the subscription is opened, and opening carries the bounded timeout every call carries | The screen is `#[Lazy]`, so its placeholder is published before `mount()` opens the stream. The stream is opened through the pinned client every call uses |
| `N1-R69` | What arrived is taken on a declared cadence, without waiting for what has not | `#[Poll(HowOften::WHILE_LISTENING_MS)]` on `HearsHowTheStackIs::listen()`. `Listeners` reads with a wait of `Listeners::NO_LONGER_THAN_MS` and stops at the first read that finds nothing |
| `N1-R70` | Twice the heartbeat in silence is a broken subscription: the last value is shown with its age, and a summary reads unknown | `WhatWasHeardSoFar::hasGoneQuiet()`, past thirty seconds with nothing heard; the screen lets go on the wake that notices. `HearingHowAStackIsTest` asserts both sides of the bound. The list holds no stream, so it keeps the same bound from when a word was heard: past `WhatWasHeardSoFar::isStillCurrent()`, a kept word reads unknown with its age |
| `N1-R71` | A broken subscription is opened again on a declared cadence and never sooner; nothing from before the break is current until a new value arrives | `WhatWasHeardSoFar::mayListen()` waits out `HowOften::AfterABreak`. A value held from before a break is drawn as unknown with its age |
| `N1-R72` | Held only while a screen showing it is in front; closed when the screen is left and when the app leaves the foreground | Every wake asks `Capture::isInFront()`, answered by `Lemonfiber.IsInFront` from the lifecycle observer capture protection installs, and lets go while the answer is no. Every way off a screen ends in `stop()`, which lets go |
| `N1-R9` | A value not read in the current session carries when it was read | A summary that is no longer current says when it was updated, and every word on the list is drawn with how long ago it was heard |
| `N1-R27` | A screen whose content changes while open refreshes on a cadence it declares, which is not shown | `#[Poll(HowOften::WHILE_LISTENING_MS)]` on `HearsHowTheStackIs::listen()`, and `HowOften::AfterABreak` in `WhatWasHeardSoFar::mayListen()`. `tests/Arch/EveryCadenceIsDeclaredTest.php` holds both and refuses a screen that hands a cadence to its template |

## How the line reaches the list

The list of stacks is the screen the app opens on, and it holds no stream:
opening the app is not a reason to talk to every stack, and the one
subscription belongs to the stack's own screen. So every word `HearsHowTheStackIs`
hears is kept, with when it was heard, and the list reads it back.

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N2-R1` | The app opens on the overall verdict, and renders `unknown` as its own answer | Every row on `YourStacks` says a word in its own sentence, and a stack never heard reads `HowItStands::Unknown` rather than nothing |
| `N1-R24` | A reading retained from an earlier session may be shown on opening, carrying when it was read, and never confirms an action | `Standings` answers `Showing`, whose held arm is a retained `Reading`. `PlatformStandings` keeps the word and the moment in the platform's store, and nothing else |
| `N2-R13` | A reading older than the session carries its age wherever it is shown, including on the opening verdict | `HowTheOneLineReads::kept()` hands the word and its age out together, and the row draws the one line's own *last heard* sentence under it |

## Why the stream is held on the screen

NativePHP offers three places a long-lived connection could live, and two of
them cannot hold this one:

- **`AsyncTask`**, the background lane, runs a task to completion and hands
  back one result through `finished()`. A subscription never completes, and a
  task there cannot be stopped once started, so the connection could not be
  closed when the operator stops looking.
- **The Vibe plugin** holds a WebSocket natively and speaks Pusher. The core
  speaks server-sent events, and a native client would be a second HTTP client
  and a second enforcement of the certificate pin, which `N1-R16` and `N1-R19`
  refuse.
- **The screen's own runloop** keeps its component alive between wakes, and
  `#[Poll]` wakes it on a declared cadence. The stream is opened through the SDK
  and held by the screen's `Listeners`, and each wake reads it with a wait of a
  millisecond, so a wake with nothing to take costs about that and never waits
  on the stack.

The third is the one used. A screen's runloop ends in `stop()` whichever way it
is left, so that is where the stream is let go of. The runloop is not told when
the app leaves the foreground, so every wake asks the bridge.
[ADR-0026](https://github.com/lemonfiber/spec/blob/main/00-overview/decisions/0026-a-screen-may-hold-the-stream.md)
has the decision and the alternatives the spec weighed.

The rest of the dashboard is not read. Each part is recorded in
`WhatTheContractCarriesThatNothingReadsTest` with the reason.
