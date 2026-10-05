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
| `N4-R7` | The app locks on backgrounding and asks for biometric or passcode to resume | `LockRule.left()` and `returned()`, fed by the process going to the background on Android and by entering the background on iOS; `WhenTheLockMoves` puts `Locked` over the screen on view (`LockTest`) |
| `N4-R8` | A biometric failure falls back to the passcode, never to unlocked | `WhatUnlocks` in each half puts the passcode behind the biometrics; `LockRule.answered()` opens only on success; `Authenticated` can be made only from the device's answer, in `PlatformAuth` (`LockTest`) |
| `N4-R9` | The task switcher shows no content | `CaptureRule` protects the window while backgrounded; `LockRule.mustCover` keeps the cover up from leaving until `Locked` is on the glass (`CaptureContractTest`) |
| `N4-R19` | The device's own authentication on a cold start and on resume after Lock after, and no prompt while an action sent is outstanding | `LockRule.coldStart()` and `returned()`; the composition root builds `Locked` in place of any screen while the lock stands; `AwaitsAnOutcome` withholds the prompt the device would raise by itself (`WhenTheLockAsks::OnlyWhenTapped`); Lock after is the period App settings keeps, told the device through `DeviceAuth::allowAway()` |
| `N4-R20` | A notification shown while locked discloses nothing of a stack | `PlatformNotifier` asks the device whether the lock stands, counting a time away not yet ended, and uses the guarded wording where it does (`NotificationTest`) |
| `N4-R22` | No authentication on a cold start where the device holds no pairing and no session | `TheLock` waives the lock where `Stacks::holdsAny()` answers no |
| `N4-R23` | That is read from the store itself, and the unlocked first run is gone once a pairing is held | `TheLock` asks `Stacks::holdsAny()` every time the lock stands; a store that will not open answers that something is held |
| `N4-R24` | Nothing is drawn behind the lock, including a first-run surface, an empty state or an earlier frame | `Locked` draws one sentence and one button; no screen is built while the lock stands; the device's cover hides the earlier frame, and the screen reader, until `TheLockIsOnTheGlass` says `Locked` was published |

## Ways past the lock, and what refuses each

| Way past | What refuses it | Held by |
|---|---|---|
| The prompt being raised read as success | `Lemonfiber.Authenticate` waits and answers the platform's success | `DeviceAuthContractTest`, `OnlyTheDeviceOpensTheLockTest` |
| A bridge answer that is missing, garbled or not a plain yes | Anything but `true` is held | `DeviceAuthContractTest` |
| An event claiming the lock opened, forged or replayed | `TheLockMoved` carries nothing; the lock is read afresh through the bridge | `NothingIsBuiltBehindTheLockTest` |
| A web request calling `Lemonfiber.Lock.Waive`, or posting an event that builds a class | The app registers no route but its screens; the NativePHP patch drops the package's `_native/api` routes | `TheAppAnswersNoWebRequestTest` |
| A link or a notification opening a screen while locked | Every screen is built through the composition root, which builds `Locked` while the lock stands | `NothingIsBuiltBehindTheLockTest` |
| The back button or an edge swipe on the lock screen | `Locked` ignores back and has no top bar | `NothingIsDrawnBehindTheLockTest` |
| A store that cannot be read, taken for an empty one | `Stacks::holdsAny()` answers that something is held | `TheLockTest` |
| The phone's clock set back, or the phone asleep | A monotonic clock that counts sleep: `elapsedRealtime`, `CLOCK_MONOTONIC` | `LockRuleTest`, `LockRuleTests` |
| The device's own prompt counted as leaving, locking again over its answer | `LockRule.left()` does nothing while the prompt is up | `LockRuleTest`, `LockRuleTests` |
| Biometrics removed or changed between locks | The passcode stands behind them in the same prompt | `LockRuleTest`, `LockRuleTests` |
| The frame from before the lock, in the switcher or on return | On Android `FLAG_SECURE`, set as the activity pauses, blanks the switcher; on both, the cover is up from leaving until `Locked` is published | `LockRuleTest`, `LockRuleTests`, on a device |
| A screen reader reading through the cover | The content is hidden from it while the cover is up | on a device |
| Two prompts answering one question | `LockRule` refuses a prompt while one is up | `LockRuleTest`, `LockRuleTests` |
| A notification disclosing a stack while locked | `PlatformNotifier` asks the lock itself | `NotifierContractTest` |
| A prompt answered to be rid of it, over an action in flight | The device asks by itself only where nothing is awaited | `NothingIsBuiltBehindTheLockTest`, `NothingIsDrawnBehindTheLockTest` |

## What a notification may carry

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N4-R10` | No credential, no household member's name, no requested title | `Notification` — all three are values, and none of them is here (`NotificationTest`) |
| `N4-R11` | The app raises no alerts of its own | every notification originates in the core's (`NotificationTest`, `WhatTheCoreDecidedTest`) |
| `N4-R15` | Not shown for a stack no longer configured | `Notification` carries the `StackId` that lets it be asked (`NotificationTest`) |

## Where a message goes

A message reaches the operator through the back-end they configured on the
stack. The app carries what the core decided and composes nothing of its own.

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N17-R1` | The app operates, requires and ships no notification service, relay or push identity of its own | `Notifier` is local: `PlatformNotifier` composes a notification on the handset through lemonfiber's own bridge, and nothing is pushed. No manifest requires a package that reaches a push service, nothing the app ships names the platform's push facade or its enrolment, the switch NativePHP's build keeps the push entitlement by is off and no Firebase file is there for it to copy, and the bridge's manifest and native code declare no push dependency, background mode or registration (`NothingButTheStackDeliversAMessageTest`) |
| `N17-R2` | Where the app subscribes to a back-end, it says what that can and cannot deliver while the app is not running, and implies no delivery it cannot provide | The app subscribes to no back-end: the only connection it opens is the SDK's, to its own stack (`NothingOpensAConnectionByHandTest`), and nothing takes a `Notifier` or asks for the notification permission, so nothing promises a delivery (`NothingButTheStackDeliversAMessageTest`). That test fails the day something does, which is when this sentence is owed |
| `N17-R6` | The app never sets or changes a back-end's credential, and never renders its value | No verb this app asks a stack for writes a credential, and `config-set` reaches only a setting the stack showed a value for (`TheAppOpensOnlyTheseDoorsTest`). A withheld setting is drawn as the stack's own note, with no control beside it (`SeeingWhatAStackIsSetToTest`). No back-end is drawn at all, which `WhatTheContractDoesNotCarryTest` holds under `N17-R5` |
| `N17-R8` | A notification carries no credential, no household member's name and no requested title | `Notification` holds the stack it is about, the core's decision and whether it is guarded, and nothing else (`NotificationTest`). What `PlatformNotifier` shows is exactly the catalogue's two lines, filled with the stack and the core's code, or the guarded pair with neither (`NotifierContractTest`) |
| `N17-R9` | The app raises no notification of its own; every one originates in the core's decisions | `Notification::fromTheCore()` is the only way to make one and takes a `WhatTheCoreDecided` and no text (`NotificationTest`). Nothing outside the module that reads the stack calls `WhatTheCoreDecided::toSay()` or `Notification::fromTheCore()` (`EveryNotificationIsTheCoresTest`), and the analyser refuses `Dialog::alert()` |

`N17-R3`, `N17-R4`, `N17-R5`, `N17-R7`, `N17-R10` and `N17-R11` wait on the
contract rather than on this app. The core sends an alert through `Channel`,
whose one implementation is the screen itself, and no envelope carries a
back-end, a delivery or a refusal of one. `WhatTheContractDoesNotCarryTest`
holds a row for each, and `N1-R17` is why none is worked out here.

## What never leaves

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N4-R5` | Retained state is discardable, and what is discarded is stated | `Configured` |
| `N4-R6` | Where there is nowhere to keep a session, the app refuses **and says why** | `Kept` — both in one value |
| `N4-R12` | Nothing is sent off the device on the app's initiative | `Assembled` — a type that could send itself would put the two one line apart (`NothingLeavesThisDeviceTest`) |
| `N4-R13` | A report is assembled *for the operator to send*, not sent | `Assembled` |
| `N4-R18` | Credentials and pairing material stay out of a capture | `Capture`, and the `concealed` half of `CaptureRule` on both platforms |
| `N4-R17` | A refused local-network permission is its own condition, not an unreachable stack | `Obstacle` |
