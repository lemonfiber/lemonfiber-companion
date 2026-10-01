# What the phone keeps

How what the phone keeps between launches is sealed before any store sees it,
and what it keeps. The code is `app-modules/seal`, the seal keys in
`app-modules/vault`, the health readings in `app-modules/health` and its store
under `src/Internal/Store`, and the ports and values they share in
`app-modules/kernel`.

Each row says what the requirement asks and what in this repository answers it.
The spec is canonical; where this page and a requirement disagree, the
requirement is right and this page is a defect.

## Sealed, under a key the platform holds

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N27-R3` | Everything kept is encrypted under a key in the platform's secure storage, readable only while the device is unlocked, and never under a key in the application's own files | `EncrypterSeal` seals with AES-256-GCM under a data key it asks `HoldsTheSealKeys` for; `PlatformSealKeys` keeps that key in the platform's store, asked for at `WhenAValueMayBeRead::WhileUnlocked`; rules `S4` and `S5` keep the encrypter and the `Crypt` facade in `Modules\Seal`; rule `A12` lets a store take and give nothing but what is sealed, and `WhatThePhoneKeepsIsUnreadableOnDiskTest` reads the database file after a summary is kept and finds none of it |
| `N27-R4` | A kept row names its stack only by a keyed hash, never by its identity, name or address | `SealedStack`, which `EncrypterSeal` builds as the HMAC-SHA256 of the stack's identity under a second key; rule `S6` keeps `hash_hmac` in `Modules\Seal` |
| `N27-R5` | With no secure storage the app keeps nothing between launches, and says so on App settings | `SealStanding` answers `Unavailable` and `Sealing` is refused with `WhyNothingIsSealed`, so nothing can be sealed and so nothing can be kept; `HowThisPhoneIsSet`, the App settings screen, asks `SecureStorage::isAvailable()` and, where there is none, says `settings.no_secure_storage` above every setting |

## Kept, and shown for what it is

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N27-R6` | Where the key cannot be read, what was kept is deleted, a new key is made, every pairing stays, and the app says once that saved data was cleared | `ClearingWhatCannotBeRead` asks `Sealed::standing()` before anything kept is opened, and where it answers `MadeAfresh` asks every store through `ForgetsEverythingKept`, which the composition root registers under one tag; `YourStacks` says `connection.saved_data_cleared` on the first frame past the lock where anything was cleared. Pairings and sessions are in the platform's secure storage, which no store touches |
| `N27-R7` | A kept reading is shown on opening with when it was read, and replaced by a fresh one when it arrives | `KeepingTheLastReading` keeps the newest summary per stack after each fresh one, sealed, and hands it back as a `WhatWasHeardSoFar` held as of when it was read; `HowThisStackIs::placeholder()` draws it on the first frame with its age, and the first summary the subscription carries replaces it |
| `N27-R10` | Readings older than the kept period are deleted when the app opens and when the period changes | `KeepingTheLastReading::forgetTheOld()` lets go of every reading older than `keptFor()`, asked by `YourStacks` on the first frame past the lock; `keepFor()`, which App settings calls with the operator's choice, lets go of them the moment it is kept; kept until removed, nothing is let go of for its age |
| `N27-R13` | A kept reading in a shape this build does not read is discarded, and a kept setting is migrated to the current shape | Every kept summary is written in a `Shape`; `HealthReadingsInTheDatabase` answers a row of a shape it has no case for as one it cannot read, and `KeepingTheLastReading` forgets it, as it forgets one that does not open or does not read as a summary. Every kept setting is in one sealed row, read by a `match` over every `Shape` in `TheSettingsAsKept`, so a shape a later build adds is an arm that carries the earlier one over; each setting is read on its own, and one it cannot make out reads as its standard and costs none of the others |
| `N27-R22` | Nothing kept is drawn while the app is locked | `BehindTheLock` builds `Locked` in place of any screen while `TheLock` stands, so no screen that reads anything kept is built, let alone drawn; `Locked` draws the reason and the unlock and reads nothing kept; `NothingIsBuiltBehindTheLockTest` and `NothingIsDrawnBehindTheLockTest` |

## Set on App settings

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N27-R9` | The operator sets how long readings are kept, from 1 to 365 whole days or until removed, and it is 30 days until they do | `HowLongReadingsAreKept`, `standard()` thirty days; `HowThisPhoneIsSet` offers `KeptFor`'s four lengths, until removed, and Other…, whose field `DaysAsked::typed()` reads and refuses outside 1 to 365; `KeepingTheLastReading::keepFor()` keeps the choice through `KeepsReadingsFor`, which `KeepingReadingsFor` keeps sealed in the phone's one settings row beside the lock's time away |
| `N27-R14` | App settings offers when to ask for the passcode again: immediately until the operator sets one, or after 1, 5 or 15 minutes, or 1 hour | `LockAfter` holds the five choices, `Immediately` its standard; `HowThisPhoneIsSet` offers them under Lock, `LockingAfter` keeps the one chosen sealed in the connection module's own settings row and tells the device through `DeviceAuth::allowAway()`, and `Locked` tells the device again each time the lock is passed, so a new process starts from the kept choice |

## Taken off the phone

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N27-R11` | Removing a stack from the phone removes every reading, setting and marker kept for it, in the same act | `ThisStackOnThisPhone`, the Stack settings page the menu ends a stack's part on, asks on the page and hands the stack to `RemovingAStack`, which writes the removal down in `RemovalsUnderWay` before anything goes, asks every `ForgetsAStack` keeper (the pairing first, then the session, the readings, the words and the work left running), and strikes the removal off only once none keeps anything of it; `PlatformStacks` leaves a stack under removal out of every list, and `YourStacks` finishes a removal left unfinished when the app opens; the app then starts over on the next stack in the order, or on a first run (`RemovingAStackFromThePhoneTest`, `RemovingAStackOnItsSettingsPageTest`) |
| `N27-R12` | Clear saved data removes every reading, setting and marker the phone keeps, and no pairing or session | `ClearingWhatThePhoneKeeps` asks every store the composition root registers as `ForgetsEverythingKept` (the readings, the phone's settings, the words and the work left running) and none that keeps a pairing or a session, then tells the device the lock is back to its standard (`ClearingWhatThePhoneKeepsLeavesPairingsTest`, `SettingHowThisPhoneIsSetTest`) |
