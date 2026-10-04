# Connection

What this application decides while a device is paired with a stack and signed
in to it.

| | |
|---|---|
| `WhatTheCodeSaysSoFar`, `WhereTheCodeGot` | A pairing code read as far as it has been typed |
| `FingerprintWasConfirmed`, `PairingWasNotConfirmed` | The operator's confirmation of the fingerprint a typed pairing shows |
| `Introducing`, `Remembering`, `HowThePairingWent` | Pairing material becoming a stack this device keeps, or updating one it already holds |
| `HowTheSignInWent` | What became of a credential offered to a stack |
| `Opening` | What the app found when it opened |
| `TheLock` | Whether the lock stands, waived where the store holds nothing it guards |
| `ClearingWhatCannotBeRead`, `WhatWasKeptAtOpening` | Everything the phone kept, cleared on opening where the key that sealed it had gone |
| `LettingGoOfOldReadings` | How long readings are kept, and every reading older than that let go of on opening and when the choice changes |

It depends on `kernel` alone. The sign-in request is made by `Admissions` in
`sdk`, through the `Admitting` port; what the phone kept is cleared through
`ForgetsEverythingKept`, which every store implements, and readings too old to
keep through `ForgetsOldReadings`, which every store of readings implements.
