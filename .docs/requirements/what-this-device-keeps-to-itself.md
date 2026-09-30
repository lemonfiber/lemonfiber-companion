# What this device keeps to itself

Permissions, notifications, the lock, and what may never leave the phone. The
code is the `N4` half of `app-modules/kernel`.

Each row says what the requirement asks and what in this repository answers it.
The spec is canonical; where this page and a requirement disagree, the
requirement is right and this page is a defect.

Several of these are kept in Kotlin and Swift rather than in PHP, and the platform
sources under `bridge/` carry no identifiers either. The rows below are the only
link between those files and the requirement they answer, which is why they name
the file and not just the type.

## Asking for a permission

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N4-R1` | The prompt belongs at first use, not on launch | `Notifier` |
| `N4-R2` | The app explains in its own words first | `Asked` |
| `N4-R3` | Every permission is optional and has a working alternative | `HowItWasRead` — typed entry is the alternative to the camera |
| `N4-R4` | A declined permission is not asked for again automatically | `Asked`, which is the record that makes it answerable |

## Getting into the app

The lock is held by the device, in `TheLock.kt` and `TheLock.swift`, and
decided by `LockRule.kt` and `LockRule.swift`. PHP reads it through the bridge
and never from an event.

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N4-R7` | The app locks on backgrounding and asks for biometric or passcode to resume | `LockRule.left()` and `returned()`, fed by the process going to the background on Android and by entering the background on iOS; `WhenTheLockMoves` puts `Locked` over the screen on view |
| `N4-R8` | A biometric failure falls back to the passcode, never to unlocked | `WhatUnlocks` in each half puts the passcode behind the biometrics; `LockRule.answered()` opens only on success; `Authenticated` can be made only from the device's answer, in `PlatformAuth` |
| `N4-R9` | The task switcher shows no content | `CaptureRule` protects the window while backgrounded; `LockRule.mustCover` keeps the cover up from leaving until `Locked` is on the glass |
| `N4-R19` | The device's own authentication on a cold start and on resume after Lock after, and no prompt while an action sent is outstanding | `LockRule.coldStart()` and `returned()`; the composition root builds `Locked` in place of any screen while the lock stands; `AwaitsAnOutcome` withholds the prompt the device would raise by itself (`WhenTheLockAsks::OnlyWhenTapped`) |
| `N4-R20` | A notification shown while locked discloses nothing of a stack | `PlatformNotifier` asks the device whether the lock stands, counting a time away not yet ended, and uses the guarded wording where it does |
| `N4-R22` | No authentication on a cold start where the device holds no pairing and no session | `TheLock` waives the lock where `Stacks::holdsAny()` answers no |
| `N4-R23` | That is read from the store itself, and the unlocked first run is gone once a pairing is held | `TheLock` asks `Stacks::holdsAny()` every time the lock stands; a store that will not open answers that something is held |
| `N4-R24` | Nothing is drawn behind the lock, including a first-run surface, an empty state or an earlier frame | `Locked` draws one sentence and one button; no screen is built while the lock stands; the device's cover hides the earlier frame, and the screen reader, until `TheLockIsOnTheGlass` says `Locked` was published |

## Ways past the lock, and what refuses each

| Way past | What refuses it | Held by |
|---|---|---|
| The prompt being raised read as success | `Lemonfiber.Authenticate` waits and answers the platform's success | `DeviceAuthContractTest`, `OnlyTheDeviceOpensTheLockTest` |
| A bridge answer that is missing, garbled or not a plain yes | Anything but `true` is held | `DeviceAuthContractTest` |
| An event claiming the lock opened, forged or replayed | `TheLockMoved` carries nothing; the lock is read afresh through the bridge | `NothingIsBuiltBehindTheLockTest` |
| A link or a notification opening a screen while locked | Every screen is built through the composition root, which builds `Locked` while the lock stands | `NothingIsBuiltBehindTheLockTest` |
| The back button or an edge swipe on the lock screen | `Locked` ignores back and has no top bar | `NothingIsDrawnBehindTheLockTest` |
| A store that cannot be read, taken for an empty one | `Stacks::holdsAny()` answers that something is held | `TheLockTest` |
| The phone's clock set back, or the phone asleep | A monotonic clock that counts sleep: `elapsedRealtime`, `CLOCK_MONOTONIC` | `LockRuleTest`, `LockRuleTests` |
| The device's own prompt counted as leaving, locking again over its answer | `LockRule.left()` does nothing while the prompt is up | `LockRuleTest`, `LockRuleTests` |
| Biometrics removed or changed between locks | The passcode stands behind them in the same prompt | `LockRuleTest`, `LockRuleTests` |
| The frame from before the lock, in the switcher or on return | The cover, up from leaving until `Locked` is published | `LockRuleTest`, `LockRuleTests`, on a device |
| A screen reader reading through the cover | The content is hidden from it while the cover is up | on a device |
| Two prompts answering one question | `LockRule` refuses a prompt while one is up | `LockRuleTest`, `LockRuleTests` |
| A notification disclosing a stack while locked | `PlatformNotifier` asks the lock itself | `NotifierContractTest` |
| A prompt answered to be rid of it, over an action in flight | The device asks by itself only where nothing is awaited | `NothingIsBuiltBehindTheLockTest`, `NothingIsDrawnBehindTheLockTest` |

## What a notification may carry

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N4-R10` | No credential, no household member's name, no requested title | `Notification` — all three are values, and none of them is here |
| `N4-R11` | The app raises no alerts of its own | every notification originates in the core's |
| `N4-R15` | Not shown for a stack no longer configured | `Notification` carries the `StackId` that lets it be asked |

## What never leaves

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N4-R5` | Retained state is discardable, and what is discarded is stated | `Configured` |
| `N4-R6` | Where there is nowhere to keep a session, the app refuses **and says why** | `Kept` — both in one value |
| `N4-R12` | Nothing is sent off the device on the app's initiative | `Assembled` — a type that could send itself would put the two one line apart |
| `N4-R13` | A report is assembled *for the operator to send*, not sent | `Assembled` |
| `N4-R18` | Credentials and pairing material stay out of a capture | `Capture`, and the `concealed` half of `CaptureRule` on both platforms |
| `N4-R17` | A refused local-network permission is its own condition, not an unreachable stack | `Obstacle` |
