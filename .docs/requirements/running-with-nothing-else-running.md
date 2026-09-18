# Running with nothing else running

The stand-in module, `app-modules/dx`: how the whole app can be launched, looked
at and demonstrated on a device with no stack anywhere near it, without any of
it reaching production.

Each row says what the requirement asks and what in this repository answers it.
The spec is canonical; where this page and a requirement disagree, the
requirement is right and this page is a defect.

## What a stand-in may be

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N1-R59` | The whole app runs and is looked at with nothing else running, against material the contract produced rather than material written by hand | `WhatAStackWouldSay` — a fixture written by its own reader proves only that both are wrong the same way |
| `N1-R61` | A released build cannot run against a stand-in | `StandsIn`, and the module being a development dependency — nothing ships that could bind one |
| `N1-R20` | The one file allowed to open a connection is still the only one | `ClientsThatReachNothing` asks `PinnedClients` for a client and hands it on, so the count is still one; `tests/Feature/NothingReachesAStackUnpinnedTest.php` |
| `N1-R60` | A stand-in writes nothing to the device | `TheStoreThisRunKeeps` — an operator who pairs a real machine while stand-ins are on must not find it gone afterwards |
| `Q-R72` | A stand-in can be admitted without editing anything outside the module | `AStandInStack` |

## Why there are three machines rather than one

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N1-R11` | A device holds more than one stack | `ADeviceAlreadyPaired` seeds every machine rather than one |
| `N1-R10` | The screens an operator meets on a bad evening exist and are reachable | `AStandInStack` holds three, and the two that refuse are the point: a build where only the working machine answers is one where those screens are never looked at |
| `N1-R22` | A machine that comes back on another address is the same machine | `AStandInStack` |
| `N1-R18` | The written form of a pinned fingerprint is sixty-four hex characters | `AStandInStack` |
| `N1-R24` | A session lives no longer than the reach it was made for, and one is held per stack | `AStackThatIsNotThere` |
| `N1-R57` | A device that has already been paired can be demonstrated, not only one that has not | `ADeviceAlreadyPaired`, with `AStackThatIsNotThere` making the stack answer |

## The flows a stand-in has to reach

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N1-R54` | The first-run sequence ends at pairing | `ACameraThatSeesAStandIn` |
| `N1-R56` | A paired device never sees that sequence again | `ACameraThatSeesAStandIn`, seeded by `ADeviceAlreadyPaired` |
| `N1-R7` | The credential exchange happens through a transport of its own, so an act that is not a reading has a stand-in too | `ADoorThatIsNotThere` |
| `N2-R4`, `N2-R5` | What a stack would put right, and what became of each | `WhatTheWireWouldAnswer` |
| `N3-R13` | The whole sequence of a refused session — offer, refuse, let go, sign in again — is reachable | `ASessionThisRunKeeps` |
| `N4-R6` | A refusal when there is nowhere to keep a session, kept per stack | `ASessionThisRunKeeps` uses the shipped adapter, so the refusal is the real one |
