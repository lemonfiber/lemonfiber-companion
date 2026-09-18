# What this device keeps to itself

Permissions, notifications, the lock, and what may never leave the phone. The
code is the `N4` half of `app-modules/kernel`.

Each row says what the requirement asks and what in this repository answers it.
The spec is canonical; where this page and a requirement disagree, the
requirement is right and this page is a defect.

Four of these are kept in Kotlin and Swift rather than in PHP, and the platform
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

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N4-R19` | The device's own authentication on a cold start | `Lock` — a newly built app is held rather than let through; `LockRule.kt` and `LockRule.swift` decide it on the device |
| `N4-R7` | That authentication is the device's, and testable | `DeviceAuth`, with a fake; `LemonfiberAuth` on both platforms is what asks the device |
| `N4-R8` | A biometric failure does not fall back to nothing | `Authenticated`, a type whose only purpose is to be hard to obtain; the accepted policy is one constant in each half of `LemonfiberAuth` |
| `N4-R22` | A lock over an empty store protects nothing | `Stacks` can be asked whether there is anything to protect, while the app is shut |
| `N4-R23` | That is read from the store rather than from a flag the app maintains | `Stacks` |

## What a notification may carry

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N4-R10` | No credential, no household member's name, no requested title | `Notification` — all three are values, and none of them is here |
| `N4-R11` | The app raises no alerts of its own | every notification originates in the core's |
| `N4-R15` | Not shown for a stack no longer configured | `Notification` carries the `StackId` that lets it be asked |
| `N4-R20` | While locked: no finding detail, no service name, no value read from a stack | `Notification` |

## What never leaves

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N4-R5` | Retained state is discardable, and what is discarded is stated | `Configured` |
| `N4-R6` | Where there is nowhere to keep a session, the app refuses **and says why** | `Kept` — both in one value |
| `N4-R9` | The app is protected while backgrounded, whatever a screen declared | `Capture`, enforced by the native half on its own — `CaptureRule` decides, `LemonfiberFunctions` applies |
| `N4-R12` | Nothing is sent off the device on the app's initiative | `Assembled` — a type that could send itself would put the two one line apart |
| `N4-R13` | A report is assembled *for the operator to send*, not sent | `Assembled` |
| `N4-R18` | Credentials and pairing material stay out of a capture | `Capture`, and the `concealed` half of `CaptureRule` on both platforms |
| `N4-R17` | A refused local-network permission is its own condition, not an unreachable stack | `Obstacle` |
