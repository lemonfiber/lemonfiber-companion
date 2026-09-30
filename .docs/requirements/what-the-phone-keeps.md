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
| `N27-R5` | With no secure storage the app keeps nothing between launches, and says so on App settings | `SealStanding` answers `Unavailable` and `Sealing` is refused with `WhyNothingIsSealed`, so nothing can be sealed and so nothing can be kept; App settings is a screen this app does not have |

## Kept, and shown for what it is

| Requirement | What it asks | What keeps it |
|---|---|---|
| `N27-R6` | Where the key cannot be read, what was kept is deleted, a new key is made, every pairing stays, and the app says once that saved data was cleared | `ClearingWhatCannotBeRead` asks `Sealed::standing()` before anything kept is opened, and where it answers `MadeAfresh` asks every store through `ForgetsEverythingKept`, which the composition root registers under one tag; `YourStacks` says `connection.saved_data_cleared` on the first frame past the lock where anything was cleared. Pairings and sessions are in the platform's secure storage, which no store touches |
| `N27-R7` | A kept reading is shown on opening with when it was read, and replaced by a fresh one when it arrives | `KeepingTheLastReading` keeps the newest summary per stack after each fresh one, sealed, and hands it back as a `WhatWasHeardSoFar` held as of when it was read; `HowThisStackIs::placeholder()` draws it on the first frame with its age, and the first summary the subscription carries replaces it |
| `N27-R10` | Readings older than the kept period are deleted when the app opens | `KeepingTheLastReading::forgetTheOld()`, thirty days until there is a setting, asked by `YourStacks` on the first frame past the lock |
| `N27-R13` | A kept reading in a shape this build does not read is discarded | Every kept summary is written in a `Shape`; `HealthReadingsInTheDatabase` answers a row of a shape it has no case for as one it cannot read, and `KeepingTheLastReading` forgets it, as it forgets one that does not open or does not read as a summary |
| `N27-R22` | Nothing kept is drawn while the app is locked | The launch draws only the unlock while locked, and asks nothing kept until the lock is passed; a stack's screen is reached only past it |
