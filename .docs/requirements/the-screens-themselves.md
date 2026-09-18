# The screens themselves

What each screen in `app-modules/operator` is for, and the requirement that put
it there. The rules every screen obeys whatever it is about are in
[what-a-screen-owes.md](what-a-screen-owes.md).

The spec is canonical; where this page and a requirement disagree, the
requirement is right and this page is a defect.

## Pairing a machine

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N1-R6` | Two roads in, a camera and typing, and the camera is the one the design was built around | `PairByScanning`, `PairByTyping`; `ADR-0018` |
| `N4-R3` | Typing exists for a camera that is refused or absent | `PairByTyping` |
| `N1-R11` | The name is asked for before the camera opens — a screen that scanned first would have nothing to call what it found | `PairByScanning` |
| `N1-R20` | Reading the code off the stack's own screen | `AStacksScreen::PairByScanning` |
| `N1-R38` | What the *operator* did is held on the screen; the reading of it is computed per frame | `YourStacks`, with `ReadingACode` |
| `N1-R49` | Expiry, and every refusal along the way, is true of both roads | one parser, not two |
| `N1-R50` | Typed pairing requires the fingerprint to be confirmed | `FingerprintWasConfirmed`, which may only exist where a person confirmed one |
| `N4-R1` | The platform's prompt is raised at the point of first use, not on launch | the camera opens when the operator asks for it |
| `N4-R2` | The app's own sentence goes up first, in front of a button; the button is what opens the camera | `PairByScanning` |
| `N4-R4` | Something declined is not re-asked for automatically | there is a button rather than an automatic retry |

## The first run, and the list of machines

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N1-R35` | A launch with no stack configured is a first run rather than a fault | `WhatTheLaunchWas::unpaired()`, and `YourStacks`' empty state |
| `N1-R36` | A launch with one is a usable frame that does not wait for a reading | `WhatTheLaunchWas::readyFor()`; the list reads no stack |
| `N1-R37` | No network, cannot reach the stack, and locked are three answers rather than one | `WhatTheLaunchWas`, stated so a test reads a screen rather than arranging a device |
| `N4-R19` | The device's own authentication on a cold start | `WhatTheLaunchWas::locked()` |
| `N1-R4` | The app says setup happens at the machine, and why, rather than omitting it | the first-run sequence |
| `N1-R54` | A sequence rather than a screen, ending at pairing | `FirstRun` |
| `N1-R55` | Every step is leavable, and leaving lands on pairing rather than on nothing | the exit is a step |
| `N1-R56` | A paired device never sees the sequence again | tied to an empty store rather than to a flag |

## What a machine is doing

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N2-R7` | Start, stop and restart, by service and by form | `WhatThisStackRuns`, `WhatToDoWithThis` |
| `N2-R8` | A disruptive action states what it disturbs, and for how long, before the yes | the yes is built from the listing and never from the tap |
| `N2-R4` | What this machine would put right, stated before any yes | `WhatWouldBePutRight` |
| `N2-R5` | Agreeing is a separate act against a named listing | `Confirmed` is built and unreachable until then |
| `N2-R6` | A yes quotes the listing it was given | the screen reads it from the fold rather than composing its own |
| `N2-R9` | What has stopped coming in, the first of four | `WhatStoppedComingIn` |
| `N2-R10` | What one service has been saying, as a bounded and searchable window | `WhatThisServiceSaid` |
| `N2-R21` | What is running here that this machine's configuration never declared | `WhatElseIsRunning` |
| `N1-R41` | An action is not presented as pending, and a job name is not persisted | nothing shows one |

## Keeping up to date

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N2-R15` | Where this machine stands on being up to date | `HowCurrentThisStackIs` |
| `N2-R16` | Installed and available are asked rather than counted | the reading answers it; a screen deciding whether tonight is worth an evening should not have to work it out |
| `N2-R17` | An offer to take a release asks first | `HowCurrentThisStackIs` |
| `N2-R18` | Four endings stay four on the row — a single *failed* is refused | the pressure to flatten lives exactly here |
| `N2-R19` | A rollback and a restore are two offers, because the difference is what an operator decides on | two sentences, not one |
| `N2-R20` | Asked of the reading rather than worked out from the list below | a stack that says it is current is not asked again |

## What the household asked for

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N2-R11` | Requests awaiting a decision are visible from a phone | `WhatTheHouseholdAsked` |
| `D7-R6` | A pending request is approvable without opening Seerr | `WhatTheHouseholdAsked::approve()` |
| `D7-R7` | Declining requires a reason, and it reaches the person who asked | the reason is held while it is written; a blank one cannot be sent |
| `D7-R3` | An estimated size is shown before a request is submitted | `HowARequestReads` |
| `D7-R4` | An estimate is labelled as one | said separately, because a single pre-composed sentence would put the two together |
| `N3-R7` | What a member was told, and when, in the stack's own words | `HowARequestReads` |

## Sessions

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N3-R13` | A refused session is offered, refused, let go of, and signed into again | `LetsGoOfARefusedSession`, written once for the screens that need it |
| `N3-R3` | Nothing rests on a template remembering | the value object is built first |
| `N1-R7` | The password is exchanged once and never retained for re-sending | `SignIn` |
